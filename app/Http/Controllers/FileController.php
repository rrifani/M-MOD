<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\File;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;




class FileController extends Controller
{
    // Menampilkan daftar file
    public function index(Request $request)
    {
        $categoryId = $request->input('category_id');
        $files = File::query();
    
        if ($categoryId) {
            $files->where('category_id', $categoryId);
        }
    
        $categories = Category::all();
        $files = $files->get(); // Ambil seluruh file yang cocok dengan filter kategori
        $folders = Storage::disk('public')->directories('folders');  // Mengambil folder
        
        // Kembalikan ke view dengan data yang dibutuhkan
        return view('files.index', compact('files', 'folders', 'categories'));
    }
    
   
    // Membuat folder baruu
    public function createFolder(Request $request)
{
    $request->validate([
        'folder_name' => 'required|string|max:255',
        'parent_folder' => 'nullable|string', // Folder induk opsional
    ]);
    
    $folderName = $request->input('folder_name');
    $parentFolder = $request->input('parent_folder'); // Bisa null jika tidak ada induk
    
    // Tentukan path folder baru
    $path = $parentFolder ? "$parentFolder/$folderName" : "folders/$folderName";
    
    // Cek apakah folder sudah ada
    if (Storage::disk('public')->exists($path)) {
        return back()->with('error', 'Folder sudah ada.');
    }
    
    // Buat folder
    Storage::disk('public')->makeDirectory($path);
    
    return back()->with('success', "Folder '$folderName' berhasil dibuat.");
}

public function deleteFolder(Request $request)
{
    $request->validate([
        'folder_name' => 'required|string',  // Nama folder yang akan dihapus
    ]);

    $folderName = $request->input('folder_name');
    // Perbaiki path folder yang akan dihapus
    $folderPaths1 = [
        "$folderName",        //menghapus dalam file
        "files/$folderName"  //menghapus dalam folder
    ];


    // Menghapus folder dari storage
    foreach ($folderPaths1 as $path) {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->deleteDirectory($path);
            Log::info("Folder '$path' berhasil dihapus.");
        } else {
            Log::info("Folder '$path' tidak ditemukan.");
        }
    }

    // Hapus semua data folder terkait di database
    File::where('folder_name', $folderName)->delete();

    return back()->with('success', "Folder '$folderName' berhasil dihapus.");
}


private function deleteFolderRecursively($path)
{
    // Memeriksa apakah folder ada
    if (Storage::disk('public')->exists($path)) {
        // Menghapus subfolder terlebih dahulu (rekursif)
        $subfolders = Storage::disk('public')->directories($path);
        foreach ($subfolders as $subfolder) {
            $this->deleteFolderRecursively($subfolder); // Hapus subfolder
        }

        // Menghapus folder setelah subfolder dihapus
        Storage::disk('public')->deleteDirectory($path);
    }
}



   
private function getAllFolders($basePath = 'folders')
{
    $folders = Storage::disk('public')->directories($basePath);
    $allFolders = [];

    foreach ($folders as $folder) {
        $allFolders[] = $folder;
        $subfolders = $this->getAllFolders($folder);  // Rekursi untuk subfolder
        $allFolders = array_merge($allFolders, $subfolders);
    }

    return $allFolders;
}



// Menampilkan form pembuatan folder atau upload
public function uploadForm()
{
    // Mengambil semua folder dalam bentuk hierarki
    $folders = $this->getAllFolders();  // Dapatkan semua folder
    $categories = Category::all();

    return view('files.upload', compact('folders', 'categories'));
}


    
    // Menyimpan file dan membuat ZIP
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:10240',  // Memastikan file tidak melebihi 10MB
            'folder_name' => 'required|string',
            'zip_folder_name' => 'required|string|max:255',  // Pastikan nama folder ZIP ada
            'category_id' => 'required|exists:categories,id',  // Validasi kategori
        ]);
    
        $files = $request->file('files');
        $folder = $request->input('folder_name');
        $zipFolderName = $request->input('zip_folder_name');
        $categoryId = $request->input('category_id');  // Ambil category_id dari request
        $folderPath = 'files/' . $folder;
    
        // Memastikan folder ada
        if (!Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->makeDirectory($folderPath);
        }
    
        // Menyimpan file dan mengumpulkan path
        $filePaths = [];
        foreach ($files as $file) {
            $path = $file->store($folderPath, 'public');
            $filePaths[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
            ];
        }
    
        // Membuat ZIP dengan nama sesuai input, dengan cek duplikasi
        $zipPath = $this->createZip($filePaths, $folderPath, $zipFolderName);
    
        // Menyimpan informasi ZIP ke database
        $fileModel = new File();
        $fileModel->user_id = Auth::id();
        $fileModel->file_name = basename($zipPath);  // Menggunakan nama ZIP yang unik
        $fileModel->file_path = $zipPath;
        $fileModel->folder_name = $folder;
        $fileModel->file_size = Storage::disk('public')->size($zipPath);
        $fileModel->category_id = $categoryId;  // Simpan kategori
        $fileModel->save();
    
        return redirect()->route('dashboard')->with('success', 'File berhasil diunggah dan dikompresi ke dalam ZIP.');
    }
    
    
    private function createZip(array $filePaths, $folderPath, $zipBaseName)
    {
        $zipFileName = $folderPath . '/' . $zipBaseName . '.zip';
        $zipFilePath = Storage::disk('public')->path($zipFileName);
    
        if (Storage::disk('public')->exists($zipFileName)) {
            $zipFileName = $folderPath . '/' . $zipBaseName . '_' . time() . '.zip';
            $zipFilePath = Storage::disk('public')->path($zipFileName);
        }
    
        $zip = new ZipArchive;
        try {
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($filePaths as $file) {
                    $fullFilePath = Storage::disk('public')->path($file['path']);
                    $zip->addFile($fullFilePath, $file['name']);
                }
                $zip->close();
    
                foreach ($filePaths as $file) {
                    Storage::disk('public')->delete($file['path']);
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP: ' . $e->getMessage());
        }
    
        return $zipFileName;
    }
    
    
    // Mengunduh file
    public function download(File $file)
    {
        $filePath = $file->file_path;

        // Memastikan file ada di storage
        if (Storage::disk('public')->exists($filePath)) {
            return response()->download(storage_path('app/public/' . $filePath));
        } else {
            return back()->withErrors('File tidak ditemukan.');
        }
    }

    // Menghapus file dari storage dan database
    public function destroy(File $file)
    {
        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return back()->with('success', 'File berhasil dihapus');
    }

    // Menampilkan konten file ZIP
    public function showContents(File $file)
    {
        $fileContents = $this->extractZipContents($file->file_path);
        return view('files.showContents', compact('file', 'fileContents'));
    }



    // Membaca konten ZIP dan mengembalikan daftar isi
    private function extractZipContents($filePath)
{
    $filePath = storage_path('app/public/' . $filePath);
    $zip = new ZipArchive;
    $fileContents = [];

    if ($zip->open($filePath) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileInZip = $zip->getNameIndex($i);
            $extension = pathinfo($fileInZip, PATHINFO_EXTENSION);

            if (in_array($extension, ['php', 'js', 'css', 'html', 'txt'])) {
                $content = $zip->getFromName($fileInZip);
                $fileContents[] = [
                    'name' => $fileInZip,
                    'content' => nl2br(e($content))
                ];
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $content = base64_encode($zip->getFromName($fileInZip));
                $fileContents[] = [
                    'name' => $fileInZip,
                    'content' => '<img src="data:image/' . $extension . ';base64,' . $content . '" alt="' . $fileInZip . '" />'
                ];
            } elseif ($extension === 'pdf') {
                $content = base64_encode($zip->getFromName($fileInZip));
                $fileContents[] = [
                    'name' => $fileInZip,
                    'content' => $content // Base64 PDF
                ];
            } else {
                $fileContents[] = [
                    'name' => $fileInZip,
                    'content' => 'File ini tidak dapat ditampilkan.'
                ];
            }
        }
        $zip->close();
    }

    return $fileContents;
}

    
// Menampilkan folder beserta subfolder
private function getFolderHierarchy($basePath = 'files/folders')
{
    $folders = Storage::disk('public')->directories($basePath);
    $folderHierarchy = [];

    foreach ($folders as $folder) {
        // Ambil subfolder secara rekursif
        $subfolders = $this->getFolderHierarchy($folder);
        $folderHierarchy[] = [
            'name' => basename($folder),
            'path' => $folder,
            'subfolders' => $subfolders,
        ];
    }

    return $folderHierarchy;
}

// Update pada index untuk menampilkan folder hierarki
public function listFolders()
{
    $search = request()->input('search');
    $folderHierarchy = $this->getFolderHierarchy();  // Ambil folder dan subfolder secara rekursif

    // Filter folder berdasarkan pencarian
    if ($search) {
        $folderHierarchy = array_filter($folderHierarchy, function($folder) use ($search) {
            return stripos($folder['name'], $search) !== false;
        });
    }

    $perPage = 10;
    $currentPage = request()->input('page', 1);
    $paginatedFolders = new LengthAwarePaginator(
        array_slice($folderHierarchy, ($currentPage - 1) * $perPage, $perPage),
        count($folderHierarchy),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('folders.index', compact('paginatedFolders', 'search'));
}

    
}