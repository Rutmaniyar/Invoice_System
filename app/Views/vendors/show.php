<section class="grid gap-6 xl:grid-cols-[0.9fr_1.2fr]" data-motion="fade-up" data-motion-stagger>
    <form method="post" action="/vendors/<?= e($vendor['id']) ?>" class="card p-5 hover:shadow-soft">
        <?= csrf_field() ?>
        <h2 class="text-lg font-black text-ink-900">Vendor details</h2>
        <div class="mt-5 space-y-4">
            <label><span class="label">Vendor name *</span><input class="field" name="name" required maxlength="190" value="<?= e(old('name', $vendor['name'])) ?>"></label>
            <label><span class="label">Contact name</span><input class="field" name="contact_name" maxlength="190" value="<?= e(old('contact_name', $vendor['contact_name'] ?? '')) ?>"></label>
            <label><span class="label">Email</span><input class="field" name="email" type="email" maxlength="190" value="<?= e(old('email', $vendor['email'] ?? '')) ?>"></label>
            <label><span class="label">Phone</span><input class="field" name="phone" maxlength="80" value="<?= e(old('phone', $vendor['phone'] ?? '')) ?>"></label>
            <label><span class="label">Website</span><input class="field" name="website" type="url" maxlength="190" value="<?= e(old('website', $vendor['website'] ?? '')) ?>"></label>
            <label><span class="label">Tax/VAT number</span><input class="field" name="tax_number" maxlength="120" value="<?= e(old('tax_number', $vendor['tax_number'] ?? '')) ?>"></label>
            <label><span class="label">Billing address</span><textarea class="textarea" name="billing_address" rows="3"><?= e(old('billing_address', $vendor['billing_address'] ?? '')) ?></textarea></label>
            <label><span class="label">Notes</span><textarea class="textarea" name="notes" rows="3"><?= e(old('notes', $vendor['notes'] ?? '')) ?></textarea></label>
        </div>
        <button class="btn-primary mt-5 w-full"><?= icon('check') ?> Update vendor</button>
    </form>

    <div class="card p-5 hover:shadow-soft">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="eyebrow">Vendor ledger</p>
                <h2 class="mt-1 text-lg font-black text-ink-900"><?= e($vendor['name']) ?></h2>
                <p class="text-sm text-ink-500"><?= e($vendor['email'] ?? 'No email on file') ?></p>
            </div>
            <a class="btn-secondary" href="/vendors"><?= icon('vendors') ?> Vendors</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Category</th><th class="text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><a class="text-brand-700 hover:underline" href="/expenses/<?= e($expense['id']) ?>"><?= e($expense['expense_date']) ?></a></td>
                            <td><?= e($expense['category']) ?></td>
                            <td class="text-right font-bold"><?= money($expense['amount'], $expense['currency']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$expenses): ?>
                        <tr><td colspan="3">
                            <?php empty_state([
                                'compact' => true,
                                'icon' => 'expenses',
                                'title' => 'No expenses for this vendor',
                                'description' => 'Expenses linked to this vendor will appear here.',
                            ]) ?>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
