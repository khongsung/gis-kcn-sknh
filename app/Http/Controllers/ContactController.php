<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contact');
    }

    public function sendMail(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'phone'    => 'required|string|max:20',
            'message'  => 'required|string',
            'address'  => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:255',
        ], [
            'fullname.required' => 'Vui lòng nhập Họ và tên',
            'phone.required'    => 'Vui lòng nhập Số điện thoại',
            'message.required'  => 'Vui lòng nhập Nội dung liên hệ',
            'email.email'       => 'Email không đúng định dạng',
        ]);

        // Gửi Mail đến địa chỉ khongsung97@gmail.com
        $toEmail = 'khongsung97@gmail.com';
        
        Mail::raw("Họ tên: {$validated['fullname']}\nSĐT: {$validated['phone']}\nEmail: {$validated['email']}\nĐịa chỉ: {$validated['address']}\nNội dung: {$validated['message']}", function ($mail) use ($toEmail, $validated) {
            $mail->to($toEmail)
                 ->subject("📩 LIÊN HỆ MỚI TỪ WEBGIS KCN SONG KHÊ - NỘI HOÀNG: {$validated['fullname']}");
        });

        return redirect()->back()->with('success', 'Gửi thông tin liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.');
    }
}