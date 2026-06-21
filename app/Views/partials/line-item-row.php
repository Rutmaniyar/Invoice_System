<div class="grid gap-3 rounded-lg border border-ink-100 bg-ink-50 p-3 md:grid-cols-[1.7fr_0.6fr_0.8fr_0.7fr_0.7fr_auto]" data-line-row>
    <label>
        <span class="label">Description</span>
        <input class="field" name="item_description[]" required>
    </label>
    <label>
        <span class="label">Qty</span>
        <input class="field" name="item_quantity[]" type="number" step="0.01" min="0" value="1" required>
    </label>
    <label>
        <span class="label">Unit price</span>
        <input class="field" name="item_unit_price[]" type="number" step="0.01" min="0" value="0.00" required>
    </label>
    <label>
        <span class="label">Discount %</span>
        <input class="field" name="item_discount_rate[]" type="number" step="0.0001" min="0" value="0">
    </label>
    <label>
        <span class="label">Tax %</span>
        <select class="field" name="item_tax_rate[]">
            <option value="0">0%</option>
            <?php foreach ($taxes as $tax): ?>
                <option value="<?= e($tax['rate']) ?>"><?= e($tax['name']) ?> · <?= percentage($tax['rate']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="flex items-end">
        <button type="button" class="btn-secondary h-10 w-10 p-0" data-remove-line aria-label="Remove line item"><?= icon('trash') ?></button>
    </div>
</div>
