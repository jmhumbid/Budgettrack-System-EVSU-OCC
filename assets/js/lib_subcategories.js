/**
 * LIB Sub-Categories Management
 * Handles sub-category functionality for "Other Maintenance and Operating Expenses"
 */

// Check if an item is "Other Maintenance and Operating Expenses"
function isOtherMaintenanceExpense(particulars) {
    const normalized = particulars.toLowerCase().trim();
    return normalized.includes('other maintenance') && normalized.includes('operating expenses');
}

// Show sub-category management modal
function showSubCategoryModal(itemId, particulars, currentAmount) {
    const modal = document.getElementById('subCategoryModal');
    if (!modal) {
        createSubCategoryModal();
    }
    
    document.getElementById('subCategoryModalTitle').textContent = `Manage Sub-Categories: ${particulars}`;
    document.getElementById('subCategoryParentId').value = itemId;
    document.getElementById('subCategoryParentAmount').textContent = `₱${parseFloat(currentAmount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    
    // Load existing sub-categories
    loadSubCategories(itemId);
    
    document.getElementById('subCategoryModal').classList.remove('hidden');
}

// Close sub-category modal
function closeSubCategoryModal() {
    document.getElementById('subCategoryModal').classList.add('hidden');
    // Reload the LIB to show updated amounts
    const libId = document.getElementById('libId').value;
    if (libId) {
        displayCurrentLIB(libId);
    }
}

// Create sub-category modal HTML
function createSubCategoryModal() {
    const modalHTML = `
        <div id="subCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[70] flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center rounded-t-xl">
                    <h3 class="text-xl font-bold text-white" id="subCategoryModalTitle">Manage Sub-Categories</h3>
                    <button onclick="closeSubCategoryModal()" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6">
                    <input type="hidden" id="subCategoryParentId">
                    
                    <!-- Add Sub-Category Form -->
                    <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="font-bold text-gray-800 mb-3">Add New Sub-Category</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Sub-Category Name</label>
                                <input type="text" id="newSubCategoryName" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600" placeholder="e.g., Office Supplies">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Amount</label>
                                <input type="number" step="0.01" min="0" id="newSubCategoryAmount" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button onclick="addSubCategory()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                Add Sub-Category
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sub-Categories List -->
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Sub-Categories</h4>
                        <div class="border-2 border-gray-300 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Sub-Category Name</th>
                                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700 w-32">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="subCategoriesTableBody">
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="border-t-2 border-gray-300 bg-gray-50 px-6 py-3 flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-700">Total Amount:</span>
                    <span class="text-2xl font-bold text-blue-600" id="subCategoryParentAmount">₱0.00</span>
                </div>
                
                <div class="border-t border-gray-200 px-6 py-4 flex justify-end">
                    <button onclick="closeSubCategoryModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Load sub-categories for a parent item
function loadSubCategories(parentId) {
    fetch(`../api/get_lib_subcategories.php?parent_id=${parentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySubCategories(data.sub_categories);
            } else {
                alert('Error loading sub-categories: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading sub-categories');
        });
}

// Display sub-categories in table
function displaySubCategories(subCategories) {
    const tbody = document.getElementById('subCategoriesTableBody');
    
    if (subCategories.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                    No sub-categories added yet. Add your first sub-category above.
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    subCategories.forEach(sub => {
        const amount = parseFloat(sub.amount);
        html += `
            <tr class="border-b border-gray-200 hover:bg-gray-50">
                <td class="px-4 py-3">${sub.sub_category_name}</td>
                <td class="px-4 py-3 text-right font-semibold">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex gap-2 justify-center">
                        <button onclick="editSubCategory(${sub.id}, '${sub.sub_category_name.replace(/'/g, "\\'")}', ${amount})" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">
                            Edit
                        </button>
                        <button onclick="deleteSubCategory(${sub.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Add new sub-category
function addSubCategory() {
    const parentId = document.getElementById('subCategoryParentId').value;
    const name = document.getElementById('newSubCategoryName').value.trim();
    const amount = parseFloat(document.getElementById('newSubCategoryAmount').value);
    
    if (!name) {
        alert('Please enter a sub-category name');
        return;
    }
    
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount greater than 0');
        return;
    }
    
    fetch('../api/add_lib_subcategory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            parent_id: parentId,
            sub_category_name: name,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear form
            document.getElementById('newSubCategoryName').value = '';
            document.getElementById('newSubCategoryAmount').value = '';
            
            // Update parent total display
            document.getElementById('subCategoryParentAmount').textContent = 
                `₱${parseFloat(data.parent_new_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            
            // Reload sub-categories list
            loadSubCategories(parentId);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the sub-category');
    });
}

// Edit sub-category (inline)
function editSubCategory(id, currentName, currentAmount) {
    const newName = prompt('Enter new sub-category name:', currentName);
    if (!newName || newName.trim() === '') return;
    
    const newAmount = prompt('Enter new amount:', currentAmount);
    if (!newAmount || parseFloat(newAmount) <= 0) return;
    
    fetch('../api/update_lib_subcategory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            sub_category_name: newName.trim(),
            amount: parseFloat(newAmount)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update parent total display
            document.getElementById('subCategoryParentAmount').textContent = 
                `₱${parseFloat(data.parent_new_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            
            // Reload sub-categories list
            const parentId = document.getElementById('subCategoryParentId').value;
            loadSubCategories(parentId);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the sub-category');
    });
}

// Delete sub-category
function deleteSubCategory(id) {
    if (!confirm('Are you sure you want to delete this sub-category?')) {
        return;
    }
    
    fetch('../api/delete_lib_subcategory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update parent total display
            document.getElementById('subCategoryParentAmount').textContent = 
                `₱${parseFloat(data.parent_new_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            
            // Reload sub-categories list
            const parentId = document.getElementById('subCategoryParentId').value;
            loadSubCategories(parentId);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the sub-category');
    });
}
