<?php

namespace App\Modules\Api\Http\Controllers\Documents;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Documents\StoreDocumentTypeRequest;
use App\Modules\Api\Http\Requests\Documents\UpdateDocumentTypeRequest;
use App\Modules\Api\Models\DocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage document types and certificate definitions for the authenticated tenant.
 */
class ApiDocumentTypeController extends ApiController
{
    /**
     * List all document types belonging to the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentType::latest();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $documentTypes = $query->get()->map->toApiArray();

        return $this->successResponse($documentTypes, 'تم جلب أنواع الوثائق بنجاح');
    }

    /**
     * Create a new document type definition.
     */
    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        $documentType = DocumentType::create($request->validated());

        return $this->successResponse($documentType->toApiArray(), 'تم إنشاء نوع الوثيقة بنجاح', 201);
    }

    /**
     * Retrieve a single document type by its UUID.
     */
    public function show(DocumentType $documentType): JsonResponse
    {
        return $this->successResponse($documentType->toApiArray(), 'تم جلب تفاصيل نوع الوثيقة بنجاح');
    }

    /**
     * Update document type details or active status.
     */
    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): JsonResponse
    {
        $documentType->update($request->validated());

        return $this->successResponse($documentType->toApiArray(), 'تم تحديث نوع الوثيقة بنجاح');
    }

    /**
     * Permanently delete a document type.
     */
    public function destroy(DocumentType $documentType): JsonResponse
    {
        $documentType->delete();

        return $this->successResponse(null, 'تم حذف نوع الوثيقة بنجاح');
    }
}
