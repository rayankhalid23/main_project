<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إيقاف القيود مؤقتاً أثناء عملية التوليد لضمان سلاسة البناء والترتيب الدقيق
        Schema::disableForeignKeyConstraints();

        // =====================================================
        // [ 1 ] جدول الأدوار (roles)
        // =====================================================
        Schema::create('roles', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->json('permissions')->nullable();
            $table->text('description')->nullable();
        });

        // =====================================================
        // [ 2 ] جدول المستخدمين الموحد (users)
        // =====================================================
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('full_name', 150);
            $table->string('email', 100)->unique();
            
            // أرقام الهواتف
            $table->string('phone_number', 20)->unique(); // رقم الهاتف الأساسي
            $table->string('alternative_phone', 20)->nullable(); // رقم هاتف احتياطي (اختياري)
        
            $table->string('password_hash', 255);
            $table->string('avatar_url', 500)->nullable();
            $table->integer('role_id'); // متطابق مع نوع جدول الأدوار
            $table->tinyInteger('is_active')->default(1);
            
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable(); // توثيق الهاتف
            $table->timestamp('last_login_at')->nullable();
            
            // [تمت إزالة حقل alternative_email من هنا بالكامل]
            
            $table->timestamps(); // ينشئ automatically created_at و updated_at
            $table->softDeletes(); // ينشئ automatically deleted_at للـ Soft Delete
        
            // القيود والفهارس (Indexes & Foreign Keys)
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict')->onUpdate('cascade');
            
            $table->index('email');
            $table->index('phone_number'); // فهرسة الهاتف لتسريع عمليات البحث
            $table->index('role_id');
            $table->index('is_active');
        });

        // =====================================================
        // [ 3 ] جدول رموز OTP (otp_codes)
        // =====================================================
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            // تغيير phone_number إلى email مع زيادة الطول ليتسع للإيميلات الطويلة
            $table->string('email', 100); 
            $table->string('code_hash', 255);
            $table->enum('purpose', ['REGISTER', 'LOGIN', 'RESET_PASSWORD', 'VERIFY_EMAIL']); // تحديث الاسم ليكون منطقياً
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false); // استخدام boolean أفضل وأوضح
            $table->tinyInteger('attempts')->default(0);
            $table->timestamp('created_at')->useCurrent();
        
            // تحديث الفهارس (Indexes) لتتوافق مع العمود الجديد
            $table->index('email');
            $table->index('expires_at');
            $table->index(['email', 'purpose', 'is_used']);
        });

        // =====================================================
        // [ 4 ] جدول الـ Refresh Tokens (refresh_tokens)
        // =====================================================
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('token_hash', 255)->unique();
            $table->unsignedBigInteger('device_id')->nullable(); // سيتم ربطه لاحقاً بجدول الأجهزة
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('token_hash');
            $table->index('expires_at');
            $table->index('revoked_at');
        });

        // =====================================================
        // [ 5 ] جدول حظر التوكنز (blacklisted_tokens)
        // =====================================================
        Schema::create('blacklisted_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 255)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('jti');
            $table->index('expires_at');
        });

        // =====================================================
        // [ 6 ] جدول أجهزة المستخدمين (user_devices)
        // =====================================================
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->text('fcm_token');
            $table->string('device_name', 100)->nullable();
            $table->enum('platform', ['ios', 'android', 'web']);
            $table->timestamp('last_active_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('user_id');
        });

        // =====================================================
        // [ 7 ] جدول المسؤولين (admins)
        // =====================================================
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict')->onUpdate('cascade');
        });

        // =====================================================
        // [ 8 ] جدول السائقين (drivers)
        // =====================================================
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            
            // التعديل: جعل الحقول التي تدخل في المرحلة الثانية اختيارية
            $table->string('national_id', 50)->unique()->nullable(); 
            $table->string('license_number', 50)->unique()->nullable();
            $table->date('license_expiry')->nullable();
            
            // التعديل: القيمة الافتراضية يجب أن تتوافق مع منطق التسجيل (Offline هو الأفضل)
            $table->enum('status', ['Pending', 'Approved', 'Suspended', 'Rejected', 'Offline', 'ON_TRIP'])->default('Offline');
            $table->enum('gender', ['male', 'female'])->default('male');
            
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(5.00);
            $table->integer('completed_trips_count')->default(0);
            $table->integer('total_subs_count')->default(0);
            $table->integer('active_subs_count')->default(0);
            $table->integer('cancelled_by_driver_count')->default(0);
            $table->integer('cancelled_by_parent_count')->default(0);
            $table->decimal('retention_rate', 5, 2)->default(100.00);
        
            $table->index('status');
            $table->index(['current_lat', 'current_lng']);
            $table->index('last_ping_at');
        });

        // =====================================================
        // [ 9 ] جدول المركبات (vehicles)
        // =====================================================
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->onUpdate('cascade');
            $table->string('plate_number', 20)->unique();
            $table->string('brand', 50);
            $table->string('model', 50);
            $table->year('year');
            $table->string('color', 30);
            $table->enum('type', ['Bus', 'Sedan', 'Van']);
            $table->integer('capacity_manual');
            $table->integer('capacity_ai')->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->string('vehicle_image_url', 500)->nullable();
            $table->tinyInteger('has_ac')->default(1);
            $table->enum('status', ['Active', 'Pending', 'Retired', 'Out'])->default('Pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('driver_id');
            $table->index('status');
        });

        // =====================================================
        // [ 10 ] جدول أولياء الأمور (parents)
        // =====================================================
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('is_trusted')->default(1);
        });

        // =====================================================
        // [ 11 ] جدول العناوين (addresses)
        // =====================================================
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade')->onUpdate('cascade');
            $table->string('label', 100)->nullable();
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->tinyInteger('is_default')->default(0);

            $table->index('parent_id');
        });

        // =====================================================
        // [ 12 ] جدول المدارس (schools)
        // =====================================================
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->text('address_text')->nullable();
            $table->enum('status', ['approved', 'pending'])->default('approved');

            $table->index(['lat', 'lng']);
        });

        // =====================================================
        // [ 13 ] جدول الأبناء (children)
        // =====================================================
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('restrict')->onUpdate('cascade');
            $table->string('full_name', 150);
            $table->date('birth_date')->nullable();
            $table->string('grade');
            $table->foreignId('home_address_id')->constrained('addresses')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('notification_radius')->default(500);
            $table->string('qr_code_token', 255)->unique()->nullable();
            $table->enum('daily_status', ['present', 'absent'])->default('present');
            $table->string('photo_url', 500)->nullable();
            $table->text('medical_notes')->nullable();
            $table->enum('preferred_time_slot', ['MORNING', 'EVENING', 'BOTH'])->default('BOTH');

            $table->index('parent_id');
            $table->index('school_id');
            $table->index('qr_code_token');
        });

        // =====================================================
        // [ 14 ] جدول الإشعارات (notifications)
        // =====================================================
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('type', ['CONTRACT', 'TRIP', 'CHAT', 'PAYMENT', 'EMERGENCY', 'SYSTEM']);
            $table->string('title', 150);
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->enum('priority', ['High', 'Low'])->default('Low');
            $table->tinyInteger('is_read')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });

        //=============================================================
        // جدول  العقد بين الطرفين 
        //=============================================================
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            
            // 1. تعريف العمود أولاً (يجب أن يكون unsignedBigInteger لأنه يطابق الـ id)
            $table->unsignedBigInteger('subscription_request_id'); 
            
            // 2. ربط المفتاح الأجنبي بهذا العمود
            $table->foreign('subscription_request_id')->references('id')->on('requests')->onDelete('cascade');
            
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            
            // باقي الحقول...
            $table->decimal('price', 8, 2);
            $table->time('pickup_time');
            $table->time('dropoff_time');
            $table->integer('max_waiting_time');
            $table->json('selected_clauses'); 
            $table->enum('status', ['pending_parent_approval', 'activated', 'rejected'])->default('pending_parent_approval');
            $table->timestamps();
        });

        //==========================================
        //    جدول الي فيه الشروط العقود 
        //==========================================

        Schema::create('clauses', function (Blueprint $table) {
            $table->id();
            
            // تصنيف الشرط (مثال: مالي، سلامة، سلوك، مواعيد) لتسهيل الفلترة في الموبايل
            $table->string('category')->default('عام'); 
            
            // نص الشرط القانوني بالتفصيل
            $table->text('clause_text'); 
            
            $table->timestamps();
        });

        // =====================================================
        // [ 15 ] جدول طلبات تعديل البيانات الحساسة للسائقين (driver_profile_changes)
        // =====================================================
        Schema::create('driver_profile_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->onUpdate('cascade');
            $table->json('old_values'); // قيم الحقول القديمة قبل التغيير للمقارنة
            $table->json('new_values'); // قيم الحقول الجديدة المطلوبة للتحديث
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->string('rejection_reason', 500)->nullable(); // سبب الرفض إن وجد
            $table->foreignId('action_by')->nullable()->constrained('admins')->onDelete('restrict'); // الأدمن المسؤول عن القرار
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('action_at')->nullable(); // وقت اتخاذ قرار القبول أو الرفض

            $table->index('driver_id');
            $table->index('status');
        });

        // =====================================================
        // [ 15 ] جدول الطلبات (requests) - تم التحديث ليدعم تفاصيل الاشتراكات
        // =====================================================
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->onUpdate('cascade');
            
            // --- الإضافات الجديدة المضافة للتوافق مع متطلباتك ---
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade')->onUpdate('cascade');
            $table->string('timing', 50); // يقبل: morning, evening, both
            // ----------------------------------------------------

            $table->enum('status', ['pending', 'accepted', 'rejected', 'contract_offered']);
            $table->text('notes')->nullable();
            $table->integer('children_count')->default(1);
            $table->timestamp('created_at')->useCurrent();

            // الفهارس (Indices) لضمان سرعة الاستعلام العالية
            $table->index('parent_id');
            $table->index('driver_id');
            $table->index('school_id'); // فهرس جديد للمدرسة
            $table->index('status');
        });

        // =====================================================
        // [ 16 ] جدول وثائق السائق (driver_documents)
        // =====================================================
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null')->onUpdate('cascade');
            $table->enum('doc_type', ['LICENSE', 'VEHICLE_LOGBOOK', 'INSURANCE', 'CRIMINAL_RECORD']);
            $table->string('file_url', 500);
            $table->date('license_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->enum('status', ['Pending', 'Verified', 'Rejected', 'Expired'])->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('restrict')->onUpdate('cascade');
            $table->text('feedback')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();

            $table->index('driver_id');
            $table->index('status');
        });


        // =====================================================
        // [ 16.1 ] جدول اعتمادات السائقين (driver_approvals)
        // =====================================================
        Schema::create('driver_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('admin_id')->constrained('admins')->onDelete('restrict')->onUpdate('cascade');
            $table->enum('status', ['Approved', 'Rejected']);
            $table->text('rejection_reason')->nullable(); // سبب الرفض إن وجد
            $table->timestamp('created_at')->useCurrent();

            // فهارس لتسريع الاستعلام
            $table->index('driver_id');
            $table->index('status');
        });

        // =====================================================
        // [ 17 ] جدول تفاصيل الطلب للأطفال (request_children)
        // =====================================================
        Schema::create('request_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('child_id')->constrained('children')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('pickup_location_id')->constrained('addresses')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('dropoff_location_id')->constrained('schools')->onDelete('restrict')->onUpdate('cascade');
            $table->text('notes')->nullable();

            $table->index('request_id');
            $table->index('child_id');
        });

        // =====================================================
        // [ 18 ] جدول المسارات (routes)
        // =====================================================
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict')->onUpdate('cascade');
            $table->string('route_name', 150);
            $table->enum('route_type', ['Morning', 'Afternoon']);
            $table->time('start_time');
            $table->json('optimized_points')->nullable();
            $table->decimal('total_distance', 8, 2)->nullable();
            $table->integer('estimated_duration')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();

            $table->index('driver_id');
            $table->index('status');
        });

        // =====================================================
        // [ 19 ] جدول الاشتراكات / العقود (subscriptions)
        // =====================================================
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_child_id')->constrained('request_children')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('parent_id')->constrained('parents')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('child_id')->constrained('children')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('home_address_id')->constrained('addresses')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('restrict')->onUpdate('cascade');
            $table->enum('contract_type', ['يومي', 'أسبوعي', 'شهري', 'فصلي']);
            $table->enum('direction', ['ذهاب', 'عودة', 'إياب']);
            $table->tinyInteger('parent_approval')->default(1);
            $table->tinyInteger('driver_approval')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('trip_price', 10, 2);
            $table->decimal('initial_amount', 10, 2);
            $table->integer('total_trips');
            $table->integer('remaining_trips');
            $table->enum('payment_status', ['Paid', 'Unpaid', 'Partial'])->default('Unpaid');
            $table->tinyInteger('is_auto_renew')->default(0);
            $table->enum('status', ['Pending_Approval', 'Active', 'Cancelled', 'Expired'])->default('Active');
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_amount', 10, 2)->default(0.00);
            $table->timestamp('created_at')->useCurrent();

            $table->index('parent_id');
            $table->index('child_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('payment_status');
        });

        // =====================================================
        // [ 20 ] جدول التعديلات اليومية للمسار (daily_route_overrides)
        // =====================================================
        Schema::create('daily_route_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade')->onUpdate('cascade');
            $table->date('effective_date');
            $table->decimal('override_lat', 10, 8)->nullable();
            $table->decimal('override_lng', 11, 8)->nullable();
            $table->tinyInteger('driver_approval')->default(1);
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

            $table->index(['effective_date', 'subscription_id']);
        });

        // =====================================================
        // [ 21 ] جدول الرحلات (trips)
        // =====================================================
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->onDelete('restrict')->onUpdate('cascade');
            $table->enum('trip_type', ['Morning', 'Afternoon']);
            $table->dateTime('scheduled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->enum('status', ['Planned', 'InProgress', 'Completed', 'Cancelled'])->default('Planned');
            $table->timestamp('created_at')->useCurrent();

            $table->index('route_id');
            $table->index('status');
            $table->index('scheduled_at');
        });

        // =====================================================
        // [ 22 ] جدول أحداث الرحلة - مسح QR (trip_events)
        // =====================================================
        Schema::create('trip_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('child_id')->constrained('children')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('restrict')->onUpdate('cascade');
            $table->enum('action_type', ['picked_up', 'dropped_off', 'absent']);
            $table->enum('trip_type', ['ذهاب', 'عودة']);
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_lng', 11, 8);
            $table->timestamp('scanned_at')->useCurrent();
            $table->decimal('trip_cost', 10, 2);

            $table->index('trip_id');
            $table->index('child_id');
            $table->index('scanned_at');
            $table->index('action_type');
        });

        // =====================================================
        // [ 23 ] تتبع الرحلة اللحظي GPS (trip_tracking)
        // =====================================================
        Schema::create('trip_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('speed_kmh', 5, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index('trip_id');
            $table->index('recorded_at');
        });

        // =====================================================
        // [ 24 ] جدول الرسائل (messages)
        // =====================================================
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->text('message_body');
            $table->enum('message_type', ['text', 'image', 'location'])->default('text');
            $table->tinyInteger('is_read')->default(0);
            $table->timestamp('sent_at')->useCurrent();

            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'is_read']);
            $table->index('sent_at');
        });

        // =====================================================
        // [ 25 ] جدول التقييمات (ratings)
        // =====================================================
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('parent_id')->constrained('parents')->onDelete('restrict')->onUpdate('cascade');
            $table->tinyInteger('stars');
            $table->text('comment')->nullable();
            $table->enum('sentiment', ['Positive', 'Negative', 'Neutral'])->nullable();
            $table->tinyInteger('is_flagged')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['trip_id', 'parent_id']);
            $table->index('trip_id');
            $table->index('parent_id');
            $table->index('is_flagged');
        });

        // =====================================================
        // [ 26 ] جدول الشكاوى (complaints)
        // =====================================================
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->constrained('parents')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('against_type', ['DRIVER', 'VEHICLE']);
            $table->unsignedBigInteger('against_id');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null')->onUpdate('cascade');
            $table->text('description');
            $table->enum('status', ['Open', 'Resolved'])->default('Open');
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->onDelete('restrict')->onUpdate('cascade');
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();

            $table->index('status');
            $table->index('submitted_by');
        });

        // =====================================================
        // [ 27 ] جدول تأكيدات الدفع (payment_confirmations)
        // =====================================================
        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('restrict')->onUpdate('cascade');
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('paid_by');
            $table->tinyInteger('confirmed_by_parent')->default(1);
            $table->tinyInteger('confirmed_by_driver')->default(0);
            $table->enum('status', ['Pending', 'Confirmed'])->default('Pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();

            $table->index('subscription_id');
            $table->index('status');
        });

        // =====================================================
        // [ 28 ] تنبيهات الطوارئ SOS (sos_alerts)
        // =====================================================
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('restrict')->onUpdate('cascade');
            $table->enum('alert_type', ['ACCIDENT', 'BREAKDOWN', 'MEDICAL']);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('status', ['Pending', 'Responded', 'Resolved'])->default('Pending');
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->onDelete('restrict')->onUpdate('cascade');
            $table->text('resolution_note')->nullable();
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->index('status');
            $table->index('trip_id');
        });

        // =====================================================
        // [ 29 ] جدول الاستثناءات والغياب (exceptions)
        // =====================================================
        Schema::create('exceptions', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['Parent', 'Driver']);
            $table->unsignedBigInteger('actor_id');
            $table->enum('target_type', ['Child', 'Route']);
            $table->unsignedBigInteger('target_id');
            $table->date('exception_date');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['exception_date', 'target_id']);
            $table->index('actor_id');
            $table->index('target_id');
        });

        // إعادة تفعيل قيود العلاقات الأجنبية فور الانتهاء التام
        Schema::enableForeignKeyConstraints();
    }

   /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إيقاف القيود مؤقتاً لضمان حذف الجداول بسلاسة وبدون ترتيب معقد للمفاتيح الأجنبية
        Schema::disableForeignKeyConstraints();
        
        $tables = [
            'driver_profile_changes', // الجدول الجديد لمراجعة بيانات السائقين الحساسة
            'contracts',              // جدول العقود المبرمة بين الأطراف
            'exceptions', 
            'sos_alerts', 
            'payment_confirmations', 
            'complaints', 
            'ratings',
            'messages', 
            'trip_tracking', 
            'trip_events', 
            'trips', 
            'daily_route_overrides',
            'subscriptions', 
            'routes', 
            'request_children', 
            'driver_documents', 
            'requests',
            'notifications', 
            'children', 
            'schools', 
            'addresses', 
            'parents', 
            'vehicles',
            'drivers', 
            'admins', 
            'user_devices', 
            'blacklisted_tokens', 
            'refresh_tokens',
            'otp_codes', 
            'users', 
            'roles'
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        // إعادة تفعيل القيود بعد اكتمال عملية التصفير بأمان
        Schema::enableForeignKeyConstraints();
    }
};