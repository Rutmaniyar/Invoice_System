<?php $selectedCurrency = (string) old('currency', $business['default_currency'] ?? 'USD'); ?>
<form method="post" action="/invoices" class="space-y-6">
    <?= csrf_field() ?>
    <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-4">
            <label class="md:col-span-2">
                <span class="label">Client</span>
                <select class="field" name="client_id" data-client-select>
                    <option value="">Select client</option>
                    <option value="__new__" <?= old('client_id') === '__new__' ? 'selected' : '' ?>>Create new client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= e($client['id']) ?>" <?= (string) old('client_id') === (string) $client['id'] ? 'selected' : '' ?>><?= e($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field-help">Choose an existing client or create one while saving this invoice.</p>
            </label>
            <label>
                <span class="label">Issue date *</span>
                <input class="field" name="issue_date" type="date" required value="<?= e(date('Y-m-d')) ?>">
            </label>
            <label>
                <span class="label">Due date *</span>
                <input class="field" name="due_date" type="date" required value="<?= e(date('Y-m-d', strtotime('+' . (int) ($business['default_payment_terms'] ?? 14) . ' days'))) ?>">
            </label>
            <label>
                <span class="label">Currency *</span>
                <select class="field" name="currency" required>
                    <?php foreach ($currencies as $currency): ?>
                        <option value="<?= e(secure_option('currency', $currency['code'])) ?>" <?= secure_option_selected('currency', $selectedCurrency, $currency['code']) ?>><?= e($currency['code']) ?> - <?= e($currency['name'] ?? $currency['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="label">Status</span>
                <select class="field" name="status">
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                </select>
            </label>
        </div>

        <div class="mt-5 hidden rounded-lg border border-brand-100 bg-brand-50/60 p-4" data-new-client-panel>
            <div class="mb-4">
                <h2 class="text-sm font-black uppercase tracking-[0.14em] text-brand-800">New client</h2>
                <p class="section-copy">A client profile will be created automatically before the invoice is saved.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label>
                    <span class="label">Client name *</span>
                    <input class="field" name="new_client_name" value="<?= e(old('new_client_name')) ?>" maxlength="190" data-new-client-required>
                </label>
                <label>
                    <span class="label">Email</span>
                    <input class="field" name="new_client_email" type="email" value="<?= e(old('new_client_email')) ?>" autocomplete="email">
                </label>
                <label class="md:col-span-2">
                    <span class="label">Billing address</span>
                    <textarea class="textarea" name="new_client_billing_address" rows="3"><?= e(old('new_client_billing_address')) ?></textarea>
                </label>
            </div>
        </div>
    </section>

    <section class="card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-black text-ink-900">Line items</h2>
            <button type="button" class="btn-secondary" data-add-line="#invoice-lines" data-template="#invoice-line-template"><?= icon('plus') ?> Add item</button>
        </div>
        <div id="invoice-lines" class="space-y-3">
            <?php \App\Core\View::partial('partials/line-item-row', ['taxes' => $taxes]); ?>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <label class="card p-5">
            <span class="label">Notes</span>
            <textarea class="textarea" name="notes" rows="5"></textarea>
        </label>
        <label class="card p-5">
            <span class="label">Terms</span>
            <textarea class="textarea" name="terms" rows="5">Payment is due by the due date shown on this invoice.</textarea>
        </label>
    </section>

    <div class="flex justify-end gap-3">
        <a href="/invoices" class="btn-secondary">Cancel</a>
        <button class="btn-primary"><?= icon('check') ?> Save invoice</button>
    </div>
</form>

<template id="invoice-line-template">
    <?php \App\Core\View::partial('partials/line-item-row', ['taxes' => $taxes]); ?>
</template>
