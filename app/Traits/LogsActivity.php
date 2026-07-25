<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Tự động đăng ký các Eloquent Events khi Model khởi chạy
     */
    public static function bootLogsActivity(): void
    {
        // 1. Khi tạo mới dữ liệu
        static::created(function ($model) {
            static::logUserAction('CREATE', $model, [
                'attributes' => $model->getAttributes(),
            ]);
        });

        // 2. Khi cập nhật dữ liệu
        static::updated(function ($model) {
            static::logUserAction('UPDATE', $model, [
                'old' => $model->getOriginal(), // Dữ liệu trước khi sửa
                'changes' => $model->getChanges(),  // Dữ liệu sau khi sửa
            ]);
        });

        // 3. Khi xóa dữ liệu
        static::deleted(function ($model) {
            static::logUserAction('DELETE', $model, [
                'attributes' => $model->getAttributes(),
            ]);
        });
    }

    /**
     * Hàm định dạng và ghi thông tin vào Laravel Log
     */
    protected static function logUserAction(string $action, $model, array $payload = []): void
    {
        $user = Auth::user();
        
        $logData = [
            'action'      => $action,
            'model'       => class_basename($model),
            'model_id'    => $model->getKey(),
            'user_id'     => $user ? $user->id : 'System/Guest',
            'user_name'   => $user ? $user->name : 'N/A',
            'user_email'  => $user ? $user->email : 'N/A',
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'data'        => $payload,
        ];

        // Ghi vào file log chính storage/logs/laravel.log
        Log::channel('audit')->info("AUDIT_LOG [{$action}] on " . class_basename($model), $logData);
    }
}