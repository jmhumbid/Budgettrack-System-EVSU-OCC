# Dashboard Fixes Required

## Issues to Fix

### 1. Utilization Card Shows Amount When No Summaries Available
**Problem:** When there are no utilization summaries, the card still shows a balance amount instead of ₱0.00

**Location:** `pages/dept_dashboard.php` (lines 390-404)

**Current Behavior:**
- Shows balance even when `utilizationCount = 0`
- "View Details" button is hidden correctly

**Fix Required:**
```php
// Change line 402 from:
<p class="text-4xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : 'text-green-700'; ?> mb-2">₱<?php echo number_format($totalBalance, 2); ?></p>

// To:
<p class="text-4xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : ($utilizationCount == 0 ? 'text-gray-400' : 'text-green-700'); ?> mb-2">₱<?php echo $utilizationCount == 0 ? '0.00' : number_format($totalBalance, 2); ?></p>
```

---

### 2. PPMP "View Details" Should Show Print-Format Table
**Problem:** The modal shows a different format than the print output

**Location:** `pages/dept_dashboard.php` (function `generatePPMPTable`, line ~1590)

**Current Format:** Custom modal table
**Required Format:** Same 12-column table as print output

**Columns Required:**
1. #
2. General Description & Objective
3. Type
4. Qty
5. Unit
6. Recommended Mode
7. Pre-Proc
8. Start
9. End Ads
10. Delivery
11. Source
12. Budget

**Fix Required:**
Replace the `generatePPMPTable` function to use the same format as the print output from `assets/js/ppmp.js`

---

### 3. LIB "View Details" Should Show Print-Format Table
**Problem:** The modal shows a different format than the print output

**Location:** `pages/dept_dashboard.php` (function `generateLIBTable`, line ~1750)

**Current Format:** Custom modal table
**Required Format:** Same table as LIB print output

**Fix Required:**
Replace the `generateLIBTable` function to use the same format as the LIB print output

---

## Implementation Steps

### Step 1: Fix Utilization Card Display

File: `pages/dept_dashboard.php`

```php
// Around line 402, replace:
<p class="text-4xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : 'text-green-700'; ?> mb-2">₱<?php echo number_format($totalBalance, 2); ?></p>

// With:
<p class="text-4xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : ($utilizationCount == 0 ? 'text-gray-400' : 'text-green-700'); ?> mb-2">
    ₱<?php echo $utilizationCount == 0 ? '0.00' : number_format($totalBalance, 2); ?>
</p>
```

### Step 2: Update PPMP Modal Table Format

File: `pages/dept_dashboard.php`

Find the `generatePPMPTable` function and replace the table HTML with:

```javascript
function generatePPMPTable(ppmp, grandTotal = 0) {
    const ppmpType = (ppmp.ppmp_type === 'supplemental') ? 'Supplemental' : 'Regular';
    const typeColor = (ppmp.ppmp_type === 'supplemental') ? 'bg-yellow-100 text-yellow-800' : 'bg-maroon text-white';
    
    return `
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="px-3 py-1 text-sm font-semibold rounded ${typeColor}">${ppmpType} PPMP</span>
                    <span class="ml-2 text-sm text-gray-600">PPMP #${ppmp.ppmp_number}</span>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Fiscal Year ${ppmp.fiscal_year}</p>
                    <p class="text-2xl font-bold text-maroon">₱${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300" style="font-size: 11px;">
                    <thead>
                        <tr class="bg-maroon text-white">
                            <th class="border border-gray-300 px-2 py-2 text-left">#</th>
                            <th class="border border-gray-300 px-2 py-2 text-left">General Description & Objective</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Type</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Qty</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Unit</th>
                            <th class="border border-gray-300 px-2 py-2 text-left">Recommended Mode</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Pre-Proc</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Start</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">End Ads</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Delivery</th>
                            <th class="border border-gray-300 px-2 py-2 text-center">Source</th>
                            <th class="border border-gray-300 px-2 py-2 text-right">Budget</th>
                        </tr>
                    </thead>
                    <tbody id="ppmpItemsBody-${ppmp.id}">
                        <tr>
                            <td colspan="12" class="border border-gray-300 px-2 py-4 text-center text-gray-500">
                                Loading items...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-maroon text-white font-bold">
                            <td colspan="11" class="border border-gray-300 px-2 py-2 text-right">GRAND TOTAL:</td>
                            <td class="border border-gray-300 px-2 py-2 text-right">₱${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    `;
}
```

### Step 3: Update populatePPMPItems Function

```javascript
function populatePPMPItems(ppmpId, items) {
    const tbody = document.getElementById(`ppmpItemsBody-${ppmpId}`);
    if (!tbody || !items || items.length === 0) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="12" class="border border-gray-300 px-2 py-4 text-center text-gray-500">No items found</td></tr>';
        return;
    }
    
    let html = '';
    items.forEach((item, index) => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="border border-gray-300 px-2 py-2 text-center">${index + 1}</td>
                <td class="border border-gray-300 px-2 py-2">${item.general_description || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${item.project_type || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-right">${parseInt(item.quantity || 0)}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${item.unit || ''}</td>
                <td class="border border-gray-300 px-2 py-2">${item.recommended_mode || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${item.pre_procurement_conference || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${formatMonth(item.start_procurement) || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${formatMonth(item.end_ads_posting) || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${formatMonth(item.expected_delivery) || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-center">${item.source_of_funds || ''}</td>
                <td class="border border-gray-300 px-2 py-2 text-right">₱${parseFloat(item.estimated_budget || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Helper function for date formatting
function formatMonth(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00' || dateStr === 'null') {
        return '';
    }
    try {
        const parts = dateStr.split('-');
        if (parts.length >= 2) {
            const year = parts[0];
            const month = parts[1];
            
            if (year === '0000' || month === '00' || !year || !month) {
                return '';
            }
            
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthIndex = parseInt(month) - 1;
            
            if (monthIndex >= 0 && monthIndex < 12) {
                return `${monthNames[monthIndex]} ${year}`;
            }
        }
        return '';
    } catch (e) {
        return '';
    }
}
```

### Step 4: Update LIB Modal Table Format

Similar approach for LIB - update the `generateLIBTable` function to match the LIB print format.

---

## Testing Checklist

### Utilization Card
- [ ] When no summaries exist, shows ₱0.00 in gray
- [ ] When summaries exist, shows correct amount in green/red
- [ ] "View Details" button hidden when no summaries

### PPMP Modal
- [ ] Shows 12-column table matching print format
- [ ] All columns properly aligned
- [ ] Grand total shows correctly
- [ ] Works for both regular and supplemental PPMP
- [ ] Date formatting matches print output

### LIB Modal
- [ ] Shows table matching LIB print format
- [ ] All columns properly aligned
- [ ] Totals show correctly
- [ ] Categories display properly

---

## Files to Modify

1. **pages/dept_dashboard.php**
   - Fix utilization card display (line ~402)
   - Update `generatePPMPTable` function (line ~1590)
   - Update `populatePPMPItems` function
   - Update `generateLIBTable` function (line ~1750)
   - Add `formatMonth` helper function

2. **pages/proc_dashboard.php** (if same issues exist)
   - Apply same fixes as dept_dashboard.php

3. **pages/admin_dashboard.php** (if same issues exist)
   - Apply same fixes as dept_dashboard.php

---

## Summary

These fixes will:
1. ✅ Show ₱0.00 when no utilization summaries exist
2. ✅ Display PPMP in print-format table (12 columns)
3. ✅ Display LIB in print-format table
4. ✅ Maintain consistency across all views
5. ✅ Improve user experience with familiar table formats

**Priority:** High  
**Complexity:** Medium  
**Estimated Time:** 2-3 hours  
**Impact:** Improves data consistency and user experience
