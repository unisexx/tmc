<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',     // ชื่อ-สกุล (ใช้กับระบบ Auth มาตรฐาน)
        'email',    // อีเมล (ล็อกอิน + ติดต่อ)
        'username', // Username สำหรับเข้าใช้งาน
        'phone',    // เบอร์โทรศัพท์ผู้ใช้
        'position', // ตำแหน่งงาน
        'password', // รหัสผ่าน (hash แล้ว)

        /* -------------------- ข้อมูล TOR --------------------*/
        'reg_purpose',                 // วัตถุประสงค์การลงทะเบียน (array: หน่วยบริการ/สสจ./สคร.)
        'reg_supervise_province_code', // รหัสจังหวัดที่กำกับดูแล (กรณีเลือก สสจ.)
        'reg_supervise_region_id',     // รหัสเขตสุขภาพที่กำกับดูแล (กรณีเลือก สคร.)

        /* -------------------- ข้อมูลหน่วยงาน --------------------*/
        'org_name',               // ชื่อหน่วยบริการ/หน่วยงาน
        'org_affiliation',        // สังกัด (เช่น กรมควบคุมโรค, กรมการแพทย์ ฯลฯ)
        'org_affiliation_other',  // กรณีเลือก "อื่น ๆ" ให้ระบุเพิ่มเติม
        'org_address',            // ที่อยู่หน่วยงาน
        'org_tel',                // เบอร์โทรศัพท์หน่วยงาน
        'org_lat',                // พิกัด Latitude
        'org_lng',                // พิกัด Longitude
        'org_working_hours',      // คำอธิบายเวลาทำการ (ข้อความอิสระ)
        'org_working_hours_json', // เวลาทำการแบบโครงสร้าง (array JSON: วัน-เวลา-หมายเหตุ)

        /* -------------------- ผู้ลงทะเบียน --------------------*/
        'contact_cid',      // บัตรประชาชน 13 หลัก
        'contact_name',     // ชื่อ-สกุลผู้ลงทะเบียน
        'contact_position', // ตำแหน่งของผู้ลงทะเบียน
        'contact_mobile',   // เบอร์มือถือผู้ลงทะเบียน

        /* -------------------- เอกสารยืนยันเจ้าหน้าที่ --------------------*/
        'officer_doc_path',        // path ไฟล์เอกสารยืนยันเจ้าหน้าที่
        'officer_doc_verified_at', // วันที่ตรวจสอบ/อนุมัติเอกสารแล้ว
        'officer_doc_verified_by', // user_id ของผู้ตรวจสอบเอกสาร

        /* -------------------- PDPA / Consent --------------------*/
        'pdpa_accepted_at', // วันที่-เวลา ที่ผู้ใช้กดยอมรับ PDPA
        'pdpa_version',     // เวอร์ชันประกาศความเป็นส่วนตัวที่ยอมรับ
        'consent_log',      // Log ความยินยอม (IP / UA / เวลา ฯลฯ) แบบ JSON

        /* -------------------- สถานะการอนุมัติ --------------------*/
        'reg_status',      // สถานะการลงทะเบียน (pending/approved/rejected)
        'reg_review_note', // หมายเหตุ/เหตุผลการพิจารณา
        'approved_at',     // วันที่อนุมัติ
        'approved_by',     // user_id ของผู้อนุมัติ

        /* -------------------- อนุญาติให้ login --------------------*/
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'       => 'datetime',
        'password'                => 'hashed',
        'reg_purpose'             => 'array',
        'org_working_hours_json'  => 'array',
        'pdpa_accepted_at'        => 'datetime',
        'officer_doc_verified_at' => 'datetime',
        'approved_at'             => 'datetime',
        'consent_log'             => 'array',
        'is_active'               => 'boolean',
    ];
}
