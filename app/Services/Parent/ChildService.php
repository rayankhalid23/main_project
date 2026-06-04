<?php

namespace App\Services\Parent;

use App\Models\Parent\Child;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ChildService
{
    /**
     * إنشاء طفل جديد في النظام مع معالجة رفع الصورة المخصصة له.
     */
    public function createChild(array $data): Child
    {
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            // تخزين المسار داخل المفتاح المطابق لقاعدة البيانات photo_url
            $data['photo_url'] = $this->uploadPhoto($data['photo']);
            unset($data['photo']); // حذف المفتاح القديم من المصفوفة
        }
    
        return Child::create($data);
    }

    /**
     * تحديث بيانات طفل موجود مسبقاً، مع معالجة تحديث الصورة وحذف القديمة.
     */
    public function updateChild(Child $child, array $data): Child
    {
        // 1. إذا قام ولي الأمر برفع صورة جديدة
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            // حذف الصورة القديمة باستخدام الحقل الصحيح photo_url
            $this->deletePhoto($child->photo_url);
            
            // رفع الصورة الجديدة وتخزينها في الحقل الصحيح
            $data['photo_url'] = $this->uploadPhoto($data['photo']);
            unset($data['photo']); // حذف المفتاح القديم لتفادي مشاكل الـ Mass Assignment
        }

        // 2. تحديث البيانات في قاعدة البيانات
        $child->update($data);

        return $child;
    }

    /**
     * حذف طفل من النظام نهائياً مع حذف صورته المرفقة.
     */
    public function deleteChild(Child $child): bool
    {
        // حذف الملف المادي للصورة باستخدام الحقل الصحيح photo_url
        $this->deletePhoto($child->photo_url);

        // حذف السجل من قاعدة البيانات
        return $child->delete();
    }

    /**
     * دالة مساعدة خاصة برفع صور الأطفال بشكل آمن ومنظم
     */
    private function uploadPhoto(UploadedFile $photo): string
    {
        // تخزين الصورة في مجلد 'children_photos' داخل قرص الـ public
        return $photo->store('children_photos', 'public');
    }

    /**
     * دالة مساعدة خاصة بحذف صور الأطفال من قرص التخزين
     */
    private function deletePhoto(?string $photoPath): void
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }
}