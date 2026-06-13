<?php

namespace App\Services\Shared;

use App\Models\Shared\Contract;
use App\Models\Shared\SubscriptionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ContractService
{
    /**
     * معالجة وإنشاء العقد وتحديث حالة الطلب داخل خادم آمن
     */
    // 1. جلب الطلب الأساسي وقفل السجل للحماية من التعديل المتزامن
    protected $table = 'requests';
    public function createContractForDriver(array $data): Contract
    {
        // استخدام Transaction لضمان تنفيذ العمليتين معاً بنجاح أو إلغائهما معاً
        return DB::transaction(function () use ($data) {
                // هذا هو السطر الذي يجلب البيانات فعلياً ويضعها في المتغير:
        $subRequest = SubscriptionRequest::findOrFail($data['subscription_request_id']);
            

            // جلب معرف السائق المرتبط بالمستخدم المسجل حالياً
            $driverId = \App\Models\Driver\Driver::where('user_id', Auth::id())->value('id');

            // المقارنة الآن تتم بين معرف السائق في الطلب ومعرف السائق الفعلي في جدول السائقين
            if ((int)$subRequest->driver_id !== (int)$driverId) {
                  throw new Exception("خطأ صلاحية: السائق الفعلي (ID: $driverId) لا يطابق صاحب الطلب (ID: {$subRequest->driver_id})", 403);
            }

            if (!in_array($subRequest->status, ['accepted'])) {
                throw new Exception("خطأ: لا يمكن إنشاء عقد. الحالة الحالية للطلب هي: " . $subRequest->status, 403);
           }

            // 4. إنشاء سجل العقد الجديد
            $contract = Contract::create([
                'subscription_request_id' => $subRequest->id,
                'parent_id'               => $subRequest->parent_id,
                'driver_id'               => Auth::id(),
                'price'                   => $data['price'],
                'pickup_time'             => $data['pickup_time'],
                'dropoff_time'            => $data['dropoff_time'],
                'max_waiting_time'        => $data['max_waiting_time'],
                'selected_clauses'        => $data['selected_clauses'],
                'status'                  => 'pending_parent_approval', // الحالة الافتراضية
            ]);

            // 5. تحديث حالة الطلب الأساسي إلى "تم تقديم العقد"
            $subRequest->update([
                'status' => 'contract_offered'
            ]);

            return $contract->load(['parent', 'driver', 'subscriptionRequest.children.school']);
        });
    }
    /**
     * موافقة وتوقيع ولي الأمر على العقد
     */
    public function acceptContract(int $id): Contract
    {
        return DB::transaction(function () use ($id) {
            $contract = Contract::lockForUpdate()->findOrFail($id);

            // تأمين: التأكد أن ولي الأمر الحالي هو صاحب العقد
            $parentProfile = \App\Models\Parent\ParentModel::where('user_id', Auth::id())->first();
            if (!$parentProfile || $contract->parent_id !== $parentProfile->id) {
                throw new Exception('غير مصرح لك بتوقيع هذا العقد.', 403);
            }

            if ($contract->status !== 'pending_parent_approval') {
                throw new Exception('لا يمكن تعديل حالة هذا العقد، قد يكون مفعلاً أو مرفوضاً مسبقاً.', 400);
            }

            // تحديث حالة العقد والطلب الأساسي
            $contract->update(['status' => 'activated']);
            $contract->subscriptionRequest->update(['status' => 'accepted']);

            return $contract->load(['parent', 'driver']);
        });
    }

    /**
     * رفض العقد من قِبل ولي الأمر
     */
    public function rejectContract(int $id): Contract
    {
        return DB::transaction(function () use ($id) {
            $contract = Contract::lockForUpdate()->findOrFail($id);

            $parentProfile = \App\Models\Parent\ParentModel::where('user_id', Auth::id())->first();
            if (!$parentProfile || $contract->parent_id !== $parentProfile->id) {
                throw new Exception('غير مصرح لك باتخاذ هذا الإجراء.', 403);
            }

            if ($contract->status !== 'pending_parent_approval') {
                throw new Exception('لا يمكن تعديل حالة هذا العقد.', 400);
            }

            // تحديث حالة العقد والطلب الأساسي للرفض
            $contract->update(['status' => 'rejected']);
            $contract->subscriptionRequest->update(['status' => 'rejected']);

            return $contract->load(['parent', 'driver']);
        });
    }
}