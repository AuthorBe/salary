<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AppSetting;

class SettingController
{
    public function index(): void
    {
        requireLogin();
        checkPermission('app_settings');

        $settingModel = new AppSetting();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            validateCsrfToken();

            $fields = [
                'company_name'   => trim($_POST['company_name'] ?? ''),
                'week_start_day' => (string)($_POST['week_start_day'] ?? '1'),
                'week_end_day'   => (string)($_POST['week_end_day'] ?? '0'),
                'timezone'       => 'Asia/Jakarta',
            ];

            // Validate
            $errors = [];
            if ($fields['company_name'] === '') {
                $errors[] = 'Nama perusahaan tidak boleh kosong.';
            }
            if (!in_array($fields['week_start_day'], ['0','1','2','3','4','5','6'], true)) {
                $errors[] = 'Hari awal minggu tidak valid.';
            }
            if (!in_array($fields['week_end_day'], ['0','1','2','3','4','5','6'], true)) {
                $errors[] = 'Hari akhir minggu tidak valid.';
            }

            if (!empty($errors)) {
                $_SESSION['flash_error'] = implode(' ', $errors);
                redirect('/settings');
            }

            foreach ($fields as $key => $value) {
                $settingModel->set($key, $value);
            }

            $_SESSION['flash_success'] = 'Setelan aplikasi berhasil disimpan.';
            redirect('/settings');
        }

        $settings = $settingModel->getAll();

        view('settings/index', [
            'title'    => 'Setelan Aplikasi – Salary',
            'pageKey'  => 'app_settings',
            'pageTitle'=> 'Setelan Aplikasi',
            'settings' => $settings,
        ]);
    }
}
