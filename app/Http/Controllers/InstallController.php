<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\SignedOption;
use App\Core\Validator;
use App\Services\InstallerService;
use App\Support\ReferenceData;

final class InstallController extends Controller
{
    public function show(): string
    {
        if (app()->isInstalled()) {
            $this->redirect('/login');
        }

        $installer = new InstallerService();
        return $this->view('install', [
            'title' => 'Install LedgerFlow',
            'checks' => $installer->requirements(),
            'timezones' => ReferenceData::timezones(),
            'currencies' => ReferenceData::currencies(),
            'countries' => ReferenceData::countries(),
        ], 'layouts/guest');
    }

    public function store(Request $request): never
    {
        if (app()->isInstalled()) {
            $this->redirect('/login');
        }

        $data = $request->all();
        $optionErrors = [];

        $timezone = SignedOption::verify('timezone', $data['timezone'] ?? '', ReferenceData::timezones());
        if ($timezone === null) {
            $optionErrors['timezone'] = 'Choose a valid timezone.';
        }
        $data['timezone'] = $timezone ?? '';

        $currency = SignedOption::verify('currency', $data['currency'] ?? '', ReferenceData::currencyCodes(ReferenceData::currencies()));
        if ($currency === null) {
            $optionErrors['currency'] = 'Choose a valid currency.';
        }
        $data['currency'] = $currency ?? '';

        if (($data['business_country'] ?? '') !== '') {
            $country = SignedOption::verify('country', $data['business_country'], ReferenceData::countries());
            if ($country === null) {
                $optionErrors['business_country'] = 'Choose a valid country.';
            }
            $data['business_country'] = $country ?? '';
        }

        $validator = (new Validator($data))
            ->required('site_url', 'Site URL')
            ->required('timezone', 'Timezone')
            ->required('db_host', 'Database host')
            ->required('db_name', 'Database name')
            ->required('db_user', 'Database username')
            ->required('currency', 'Currency')
            ->required('business_name', 'Business name')
            ->required('business_email', 'Business email')
            ->email('business_email', 'Business email')
            ->required('admin_name', 'Admin name')
            ->required('admin_email', 'Admin email')
            ->email('admin_email', 'Admin email')
            ->required('admin_password', 'Admin password');

        if (($data['admin_password'] ?? '') !== ($data['admin_password_confirmation'] ?? '')) {
            $errors = array_merge($validator->errors(), $optionErrors);
            $errors['admin_password_confirmation'] = 'Password confirmation does not match.';
            Session::flash('errors', $errors);
            Session::flash('_old', $data);
            $this->redirect('/install');
        }

        if (strlen((string) ($data['admin_password'] ?? '')) < 10) {
            $errors = array_merge($validator->errors(), $optionErrors);
            $errors['admin_password'] = 'Admin password must be at least 10 characters.';
            Session::flash('errors', $errors);
            Session::flash('_old', $data);
            $this->redirect('/install');
        }

        if ($validator->fails() || $optionErrors !== []) {
            Session::flash('errors', array_merge($validator->errors(), $optionErrors));
            Session::flash('_old', $data);
            $this->redirect('/install');
        }

        try {
            (new InstallerService())->install($data);
        } catch (\Throwable $exception) {
            Session::flash('errors', ['install' => $exception->getMessage()]);
            Session::flash('_old', $data);
            $this->redirect('/install');
        }

        Session::flash('success', 'Installation complete. Sign in with your administrator account.');
        $this->redirect('/login');
    }
}
