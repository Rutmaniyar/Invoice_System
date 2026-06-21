<section class="card p-5 hover:shadow-soft">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="eyebrow">Pipeline</p>
            <h2 class="mt-1 text-lg font-black text-ink-900">Quotes</h2>
            <p class="text-sm text-ink-500">Create estimates and convert accepted quotes into invoices.</p>
        </div>
        <a href="/quotes/create" class="btn-primary"><?= icon('plus') ?> New quote</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Quote</th><th>Client</th><th>Status</th><th>Valid until</th><th class="text-right">Total</th></tr></thead>
            <tbody class="divide-y divide-ink-100">
                <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td><a class="font-bold text-brand-700" href="/quotes/<?= e($quote['id']) ?>"><?= e($quote['quote_number']) ?></a></td>
                        <td><?= e($quote['client_name']) ?></td>
                        <td><span class="badge bg-ink-100 text-ink-700"><?= e($quote['status']) ?></span></td>
                        <td><?= e($quote['valid_until']) ?></td>
                        <td class="text-right font-bold"><?= money($quote['total'], $quote['currency']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$quotes): ?><tr><td colspan="5"><div class="empty-state my-4"><p class="font-bold text-ink-800">No quotes yet</p><p class="mt-1 text-sm text-ink-500">Create an estimate and convert it when accepted.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="table-footer">
            <span>Showing <?= e(count($quotes)) ?> quotes</span>
            <span class="hidden sm:inline">Accepted estimates can be converted into invoices.</span>
        </div>
    </div>
</section>
