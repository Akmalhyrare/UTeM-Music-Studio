<?php

namespace Database\Seeders;

use Database\Seeders\Support\MalaysianData;
use Illuminate\Database\Seeder;
use App\Models\Staff;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // [full_name, email, position, password, is_admin]
        $staff = [
            ['Mohd Faizal Hassan', 'faizal@utem.edu.my', 'Administrator', 'Staff!Faizal27', true],
            ['Nurul Syazana binti Ahmad', 'syazana@utem.edu.my', 'Studio Technician', 'Staff!Syazana27', false],
            ['Ahmad Danial bin Zulkifli', 'danial@utem.edu.my', 'Maintenance Officer', 'Student@Danial20', false],
            ['Mohd Syafiq bin Karim', 'syafiq@utem.edu.my', 'Studio Technician', 'Syafiq#UTeM26', false],
            ['Amirul Asyraf bin Yusof', 'amirul@utem.edu.my', 'Front Desk Officer', 'Amirul#Campus26', false],
            ['Hafiz Iskandar bin Mahmud', 'hafiz@utem.edu.my', 'Studio Technician', 'Hafiz_60!', false],
        ];

        foreach ($staff as [$fullName, $email, $position, $password, $isAdmin]) {
            Staff::firstOrCreate(
                ['email' => $email],
                array_merge(
                    MalaysianData::fixedAccountWithPassword($fullName, $email, $position, $password),
                    ['position' => $position, 'status' => 'active', 'is_admin' => $isAdmin]
                )
            );
        }
    }
}
