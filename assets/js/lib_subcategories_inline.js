/**
 * LIB Sub-Categories Inline Management
 * Handles sub-categories when "Other Maintenance and Operating Expenses" is clicked from dropdown
 */

// Track sub-categories
let subCategoriesData = {};
let inlineSubCategoriesData = {};

// Check if particulars is "Other Maintenance and Operating Expenses"
function isOtherMaintenanceExpense(particulars) {
    if (!particulars) return false;
    const normalized = particulars.toLowerCase().trim();
    return normalized.includes('other maintenance') && normalized.includes('operating expenses');
}

// Show inline sub-category section (for inline add item when clicking from dropdown)
function showInlineSubCategorySection(categoryKey) {
    let subCategorySection = document.getElementById(`inlineSubCategorySection_${categoryKey}`);
    
    if (!subCategorySection) {
        const addItemRow = document.getElementById(`addItemRow_${categoryKey}`);
        if (!addItemRow) return;
        
        const newRow = document.createElement('tr');
        newRow.id = `inlineSubCategorySection_${categoryKey}`;
        newRow.className = 'bg-blue-50';
        newRow.innerHTML = `
            <td colspan="3" class="border border-gray-300 p-3">
                <div class="border-2 border-blue-300 rounded-lg p-3 bg-white">
                    <div class="flex justify-between items-center mb-2">
                        <h5 class="font-bold text-sm text-blue-900">Sub-Categories</h5>
                        <button type="button" onclick="addInlineSubCategory('${categoryKey}')" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs font-semibold">
                            + Add Sub-Category
                        </button>
                    </div>
                    <div id="inlineSubCategoriesList_${categoryKey}" class="space-y-2">
                        <p class="text-xs text-gray-500 italic">No sub-categories yet. Click "Add Sub-Category" to add one.</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-blue-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">Total Amount:</span>
                            <span class="text-lg font-bold text-blue-600" id="inlineSubCategoryTotal_${categoryKey}">₱0.00</span>
                        </div>
                    </div>
                </div>
            </td>
        `;
        
        addItemRow.parentNode.insertBefore(newRow, addItemRow.nextSibling);
        
        if (!inlineSubCategoriesData[categoryKey]) {
            inlineSubCategoriesData[categoryKey] = [];
        }
        
        const amountInput = document.getElementById(`newAmount_${categoryKey}`);
        if (amountInput) {
            amountInput.value = '0.00';
            amountInput.readOnly = true;
            amountInput.classList.add('bg-gray-100');
            amountInput.title = 'Amount is calculated from sub-categories';
        }
    }
}

// Hide inline sub-category section
function hideInlineSubCategorySection(categoryKey) {
    const subCategorySection = document.getElementById(`inlineSubCategorySection_${categoryKey}`);
    if (subCategorySection) {
        subCategorySection.remove();
        delete inlineSubCategoriesData[categoryKey];
        
        const amountInput = document.getElementById(`newAmount_${categoryKey}`);
        if (amountInput) {
            amountInput.readOnly = false;
            amountInput.classList.remove('bg-gray-100');
            amountInput.title = '';
            amountInput.value = '';
            amountInput.focus();
        }
    }
}

// Add inline sub-category
function addInlineSubCategory(categoryKey) {
    const container = document.getElementById(`inlineSubCategoriesList_${categoryKey}`);
    if (!container) return;
    
    const noItemsMsg = container.querySelector('p.italic');
    if (noItemsMsg) noItemsMsg.remove();
    
    const subId = Date.now();
    
    const subRow = document.createElement('div');
    subRow.id = `inlineSubCategory_${subId}`;
    subRow.className = 'flex gap-2 items-center bg-gray-50 p-2 rounded border border-gray-200';
    subRow.innerHTML = `
        <div class="flex-1">
            <input type="text" 
                   placeholder="Sub-category name (e.g., Office Supplies)" 
                   class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                   id="inlineSubCategoryName_${subId}"
                   onchange="updateInlineSubCategoryTotal('${categoryKey}')">
        </div>
        <div class="w-32">
            <input type="number" 
                   step="0.01" 
                   min="0" 
                   placeholder="0.00" 
                   class="w-full px-2 py-1 border border-gray-300 rounded text-sm text-right"
                   id="inlineSubCategoryAmount_${subId}"
                   onchange="updateInlineSubCategoryTotal('${categoryKey}')">
        </div>
        <button type="button" 
                onclick="removeInlineSubCategory('${categoryKey}', ${subId})" 
                class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs"
                title="Remove">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    
    container.appendChild(subRow);
    
    if (!inlineSubCategoriesData[categoryKey]) {
        inlineSubCategoriesData[categoryKey] = [];
    }
    inlineSubCategoriesData[categoryKey].push({
        id: subId,
        name: '',
        amount: 0
    });
}

// Remove inline sub-category
function removeInlineSubCategory(categoryKey, subId) {
    const subRow = document.getElementById(`inlineSubCategory_${subId}`);
    if (subRow) subRow.remove();
    
    if (inlineSubCategoriesData[categoryKey]) {
        inlineSubCategoriesData[categoryKey] = inlineSubCategoriesData[categoryKey].filter(sub => sub.id !== subId);
    }
    
    updateInlineSubCategoryTotal(categoryKey);
    
    const container = document.getElementById(`inlineSubCategoriesList_${categoryKey}`);
    if (container && container.children.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-500 italic">No sub-categories yet. Click "Add Sub-Category" to add one.</p>';
    }
}

// Update inline sub-category total
function updateInlineSubCategoryTotal(categoryKey) {
    let total = 0;
    
    if (inlineSubCategoriesData[categoryKey]) {
        inlineSubCategoriesData[categoryKey].forEach(sub => {
            const nameInput = document.getElementById(`inlineSubCategoryName_${sub.id}`);
            const amountInput = document.getElementById(`inlineSubCategoryAmount_${sub.id}`);
            
            if (nameInput && amountInput) {
                sub.name = nameInput.value;
                sub.amount = parseFloat(amountInput.value) || 0;
                total += sub.amount;
            }
        });
    }
    
    const totalDisplay = document.getElementById(`inlineSubCategoryTotal_${categoryKey}`);
    if (totalDisplay) {
        totalDisplay.textContent = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    
    const amountInput = document.getElementById(`newAmount_${categoryKey}`);
    if (amountInput) {
        amountInput.value = total.toFixed(2);
    }
}

// Get inline sub-categories for saving
function getInlineSubCategories(categoryKey) {
    if (!inlineSubCategoriesData[categoryKey]) return [];
    
    return inlineSubCategoriesData[categoryKey]
        .map(sub => ({
            name: document.getElementById(`inlineSubCategoryName_${sub.id}`)?.value || '',
            amount: parseFloat(document.getElementById(`inlineSubCategoryAmount_${sub.id}`)?.value) || 0
        }))
        .filter(sub => sub.name && sub.amount > 0);
}
