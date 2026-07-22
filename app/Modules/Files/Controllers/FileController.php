<?php

namespace App\Modules\Files\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Files\Services\FileService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    use ApiResponse;

    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        $folderId = $request->query('folder_id', 'root');
        $files = $this->fileService->getUserFiles($folderId);
        return $this->successResponse($files, 'Files retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'folder_id' => 'required|string',
        ]);

        $file = $this->fileService->uploadFile($request->only('folder_id'), $request->file('file'));
        return $this->successResponse($file, 'File uploaded successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $file = $this->fileService->renameFile($id, $request->input('name'));
        return $this->successResponse($file, 'File renamed successfully');
    }

    public function destroy($id)
    {
        $this->fileService->deleteFile($id);
        return $this->successResponse(null, 'File deleted successfully');
    }

    public function sync(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'original_name' => 'required|string',
            'size' => 'required|integer',
            'storage_path' => 'required|string',
        ]);

        $file = $this->fileService->sync($request->all());
        return $this->successResponse($file, 'File synced successfully', 201);
    }

    public function syncDelete(Request $request)
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'required|string',
        ]);

        $this->fileService->syncDelete($request->paths);
        return $this->successResponse(null, 'Files synced deletion successfully');
    }
}
