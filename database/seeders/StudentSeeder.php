<?php

namespace Database\Seeders;

use Database\Seeders\Support\MalaysianData;
use Illuminate\Database\Seeder;
use App\Models\Student;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        // [full_name, email, password, status, matric_no]
        $students = [
            ['Muhammad Amir Hakimi bin Razali', 'amir@student.utem.edu.my', 'Amir_123', 'active', 'B0322101234'],
            ['Muhammad Haziq bin Salleh', 'haziq@student.utem.edu.my', 'Staff!Haziq26', 'active', null],
            ['Wan Mohd Hisyam bin Wan Ahmad', 'hisyam@student.utem.edu.my', 'Hisyam#UTeM27', 'active', null],
            ['Faris Aiman bin Rosli', 'faris@student.utem.edu.my', 'Student@Faris29', 'active', null],
            ['Mohd Zulfadli bin Othman', 'zulfadli@student.utem.edu.my', 'Student@Zulfadli12', 'active', null],
            ['Iqbal Hakim bin Anuar', 'iqbal@student.utem.edu.my', 'Iqbal#Campus24', 'active', null],
            ['Mohd Firdaus bin Sulaiman', 'firdaus@student.utem.edu.my', 'Staff!Firdaus25', 'active', null],
            ['Azri Hafiz bin Mat Som', 'azri@student.utem.edu.my', 'Azri#UTeM27', 'active', null],
            ['Nur Aisyah binti Mohd Faiz', 'aisyah@student.utem.edu.my', 'Staff!Aisyah25', 'active', null],
            ['Nurul Huda binti Ismail', 'huda@student.utem.edu.my', 'Huda$62', 'active', null],
            ['Siti Nurhaliza binti Ahmad', 'nurhaliza@student.utem.edu.my', 'Staff!Nurhaliza25', 'active', null],
            ['Aina Sofea binti Abdul Rahman', 'aina@student.utem.edu.my', 'Aina#Campus27', 'active', null],
            ['Farah Adibah binti Hassan', 'farah@student.utem.edu.my', 'Staff!Farah27', 'active', null],
            ['Nur Atiqah binti Roslan', 'atiqah@student.utem.edu.my', 'Atiqah#UTeM25', 'active', null],
            ['Wan Nur Izzati binti Wan Zahari', 'izzati@student.utem.edu.my', 'Izzati#Campus26', 'active', null],
            ['Nadia Batrisyia binti Azlan', 'nadia@student.utem.edu.my', 'Student@Nadia30', 'active', null],
            ['Siti Maisarah binti Yaakob', 'maisarah@student.utem.edu.my', 'Maisarah$71', 'active', null],
            ['Tan Jia Wei', 'jiawei@student.utem.edu.my', 'Staff!Jiawei24', 'active', null],
            ['Lim Wei Xuan', 'weixuan@student.utem.edu.my', 'Weixuan@2026', 'active', null],
            ['Wong Mei Ling', 'meiling@student.utem.edu.my', 'Meiling_57!', 'active', null],
            ['Lee Chong Wei', 'chongwei@student.utem.edu.my', 'Chongwei#Campus27', 'active', null],
            ['Chong Sze Ying', 'szeying@student.utem.edu.my', 'Szeying_57!', 'active', null],
            ['Ng Hui Min', 'huimin@student.utem.edu.my', 'Huimin$16', 'active', null],
            ['Tan Wei Ming', 'weiming@student.utem.edu.my', 'Weiming#UTeM26', 'active', null],
            ['Goh Zi Xuan', 'zixuan@student.utem.edu.my', 'Zixuan$89', 'active', null],
            ['Raj Kumar a/l Suresh', 'raj@student.utem.edu.my', 'Raj$77', 'active', null],
            ['Priya Devi a/p Ramesh', 'priya@student.utem.edu.my', 'Priya@2024', 'active', null],
            ['Karthik a/l Subramaniam', 'karthik@student.utem.edu.my', 'UTeM_Karthik27', 'active', null],
            ['Deepa a/p Manoharan', 'deepa@student.utem.edu.my', 'Deepa_35!', 'active', null],
            ['Vimal Raj a/l Anand', 'vimal@student.utem.edu.my', 'Vimal$80', 'active', null],
            ['Kavitha a/p Murugan', 'kavitha@student.utem.edu.my', 'Student@Kavitha93', 'active', null],
            ['Muhammad Amir Hakimi bin Razali', 'amir2@student.utem.edu.my', 'Amir2#UTeM24', 'inactive', null],
        ];

        foreach ($students as [$fullName, $email, $password, $status, $matricNo]) {
            Student::firstOrCreate(
                ['email' => $email],
                array_merge(
                    MalaysianData::fixedAccountWithPassword($fullName, $email, 'Student', $password),
                    ['matric_no' => $matricNo ?? MalaysianData::uniqueMatricNo(), 'status' => $status]
                )
            );
        }
    }
}
