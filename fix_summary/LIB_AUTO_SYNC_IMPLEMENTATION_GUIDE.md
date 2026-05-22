# LIB Auto-Sync System Implementation Guide

## Overview
The Auto-Sync LIB system automatically generates Line Item Budget (LIB) entries from approved budget allocations, with the ability to add custom items that aren't derived from allocations.

## Database Structure

### New Table: `lib_custom_items`
```sql
CREATE TABLE IF NOT EXISTS lib_custom_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    year INT NOT NULL,
    uacs_code VARCHAR(50) NOT NULL,
    general_desc TEXT NOT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    quarter_1 DECIMAL(15,2) DEFAULT 0.00,
    quarter_2 DECIMAL(15,2) DEFAULT 0.00,
    quarter_3 DECIMAL(15,2) DEFAULT 0.00,
    quarter_4 DECIMAL(15,2) DEFAULT 0.00,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    deleted_by INT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (deleted_by) REFERENCES users(id),
    INDEX idx_dept_year (department_id, year),
    INDEX idx_deleted (deleted_at)
);
```

## API Endpoints Created

### 1. `api/generate_auto_lib.php`
**Purpose:** Auto-generates LIB from allocations and includes custom items

**Method:** POST

**Parameters:**
- `department_id` (required): Department ID
- `year` (optional): Fiscal year (defaults to current year)

**Response:**
```json
{
    "success": true,
    "items": [
        {
            "allocation_id": 123,
            "uacs_code": "5-02-01-010",
            "general_desc": "Salaries and Wages",
            "total_amount": 500000.00,
            "quarter_1": 125000.00,
            "quarter_2": 125000.00,
            "quarter_3": 125000.00,
            "quarter_4": 125000.00,
            "source": "allocation",
            "is_custom": false
        },
        {
            "custom_item_id": 5,
            "uacs_code": "5-02-03-050",
            "general_desc": "Special Project Expenses",
            "total_amount": 50000.00,
            "quarter_1": 12500.00,
            "quarter_2": 12500.00,
            "quarter_3": 12500.00,
            "quarter_4": 12500.00,
            "source": "custom",
            "is_custom": true
        }
    ],
    "department_name": "Computer Studies",
    "year": "2026"
}
```

### 2. `api/add_lib_custom_item.php`
**Purpose:** Add a custom item to LIB (not from allocations)

**Method:** POST (JSON body)

**Parameters:**
```json
{
    "department_id": 1,
    "year": 2026,
    "uacs_code": "5-02-03-050",
    "general_desc": "Special Project Expenses",
    "total_amount": 50000.00,
    "quarter_1": 12500.00,
    "quarter_2": 12500.00,
    "quarter_3": 12500.00,
    "quarter_4": 12500.00
}
```

**Response:**
```json
{
    "success": true,
    "message": "Custom item added successfully",
    "custom_item_id": 5
}
```

### 3. `api/update_lib_custom_item.php`
**Purpose:** Update an existing custom LIB item

**Method:** POST (JSON body)

**Parameters:**
```json
{
    "custom_item_id": 5,
    "uacs_code": "5-02-03-050",
    "general_desc": "Updated Description",
    "total_amount": 60000.00,
    "quarter_1": 15000.00,
    "quarter_2": 15000.00,
    "quarter_3": 15000.00,
    "quarter_4": 15000.00
}
```

### 4. `api/delete_lib_custom_item.php`
**Purpose:** Soft delete a custom LIB item

**Method:** POST (JSON body)

**Parameters:**
```json
{
    "custom_item_id": 5
}
```

## Installation

### Step 1: Create Database Table
Run the installation script:
```bash
php install_lib_custom_items.php
```

This will create the `lib_custom_items` table.

### Step 2: Verify API Endpoints
Ensure all API files are in the `api/` directory:
- `api/generate_auto_lib.php`
- `api/add_lib_custom_item.php`
- `api/update_lib_custom_item.php`
- `api/delete_lib_custom_item.php`

## Frontend Integration

### Modify `pages/lib.php`

#### 1. Add "Auto-Generate from Allocations" Button
In the action buttons section, add:
```html
<button onclick="showAutoGenerateLIBModal()" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all font-semibold flex items-center gap-2 shadow-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    Auto-Generate from Allocations
</button>
```

#### 2. Add Auto-Generate Modal
```html
<!-- Auto-Generate LIB Modal -->
<div id="autoGenerateLIBModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full mx-4 max-h-[90vh] flex flex-col">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-2xl font-bold text-white">Auto-Generate LIB from Allocations</h3>
            <button onclick="closeAutoGenerateLIBModal()" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Select Year</label>
                <select id="autoGenYear" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600">
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                </select>
            </div>
            
            <div class="mb-4">
                <button onclick="generateAutoLIB()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    Generate LIB
                </button>
            </div>
            
            <div id="autoGenPreview" class="hidden">
                <h4 class="text-lg font-bold text-gray-800 mb-3">Generated LIB Items</h4>
                <div class="border-2 border-gray-300 rounded-lg overflow-auto max-h-96">
                    <table class="w-full">
                        <thead class="bg-green-600 text-white sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left">Source</th>
                                <th class="px-4 py-2 text-left">UACS Code</th>
                                <th class="px-4 py-2 text-left">Description</th>
                                <th class="px-4 py-2 text-right">Total Amount</th>
                                <th class="px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="autoGenTableBody">
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 flex justify-between items-center">
                    <button onclick="showAddCustomItemModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Custom Item
                    </button>
                    <div class="text-right">
                        <span class="text-lg font-bold text-gray-700">Grand Total: </span>
                        <span class="text-2xl font-bold text-green-600" id="autoGenGrandTotal">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
            <button onclick="closeAutoGenerateLIBModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Cancel
            </button>
            <button onclick="saveAutoGeneratedLIB()" id="saveAutoGenBtn" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 hidden">
                Save LIB
            </button>
        </div>
    </div>
</div>

<!-- Add Custom Item Modal -->
<div id="addCustomItemModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60] flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-xl font-bold text-white">Add Custom LIB Item</h3>
            <button onclick="closeAddCustomItemModal()" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            <form id="customItemForm">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">UACS Code</label>
                        <input type="text" id="customUACSCode" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Total Amount</label>
                        <input type="number" step="0.01" id="customTotalAmount" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea id="customDescription" required rows="3" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600"></textarea>
                </div>
                
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q1</label>
                        <input type="number" step="0.01" id="customQ1" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q2</label>
                        <input type="number" step="0.01" id="customQ2" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q3</label>
                        <input type="number" step="0.01" id="customQ3" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q4</label>
                        <input type="number" step="0.01" id="customQ4" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAddCustomItemModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

#### 3. Add JavaScript Functions
Add these functions to the `<script>` section:

```javascript
let autoGeneratedItems = [];
let currentAutoGenYear = new Date().getFullYear();

function showAutoGenerateLIBModal() {
    document.getElementById('autoGenerateLIBModal').classList.remove('hidden');
    document.getElementById('autoGenYear').value = currentAutoGenYear;
}

function closeAutoGenerateLIBModal() {
    document.getElementById('autoGenerateLIBModal').classList.add('hidden');
    autoGeneratedItems = [];
}

function generateAutoLIB() {
    const year = document.getElementById('autoGenYear').value;
    currentAutoGenYear = year;
    
    const formData = new FormData();
    formData.append('department_id', window.DEPARTMENT_ID);
    formData.append('year', year);
    
    fetch('../api/generate_auto_lib.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            autoGeneratedItems = data.items;
            displayAutoGeneratedItems();
            document.getElementById('autoGenPreview').classList.remove('hidden');
            document.getElementById('saveAutoGenBtn').classList.remove('hidden');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating LIB');
    });
}

function displayAutoGeneratedItems() {
    const tbody = document.getElementById('autoGenTableBody');
    tbody.innerHTML = '';
    
    let grandTotal = 0;
    
    autoGeneratedItems.forEach((item, index) => {
        const amount = parseFloat(item.total_amount);
        grandTotal += amount;
        
        const sourceLabel = item.is_custom ? 
            '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Custom</span>' :
            '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Allocation</span>';
        
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 border-b border-gray-200';
        row.innerHTML = `
            <td class="px-4 py-2">${sourceLabel}</td>
            <td class="px-4 py-2 font-mono text-sm">${item.uacs_code}</td>
            <td class="px-4 py-2">${item.general_desc}</td>
            <td class="px-4 py-2 text-right font-semibold">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="px-4 py-2 text-center">
                ${item.is_custom ? `
                    <button onclick="editCustomItem(${index})" class="text-blue-600 hover:text-blue-800 mr-2">
                        <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button onclick="deleteCustomItem(${index})" class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                ` : '<span class="text-gray-400 text-xs">From Allocation</span>'}
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('autoGenGrandTotal').textContent = 
        '₱' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
}

function showAddCustomItemModal() {
    document.getElementById('addCustomItemModal').classList.remove('hidden');
    document.getElementById('customItemForm').reset();
}

function closeAddCustomItemModal() {
    document.getElementById('addCustomItemModal').classList.add('hidden');
}

// Handle custom item form submission
document.getElementById('customItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const customItem = {
        uacs_code: document.getElementById('customUACSCode').value,
        general_desc: document.getElementById('customDescription').value,
        total_amount: parseFloat(document.getElementById('customTotalAmount').value),
        quarter_1: parseFloat(document.getElementById('customQ1').value) || 0,
        quarter_2: parseFloat(document.getElementById('customQ2').value) || 0,
        quarter_3: parseFloat(document.getElementById('customQ3').value) || 0,
        quarter_4: parseFloat(document.getElementById('customQ4').value) || 0,
        source: 'custom',
        is_custom: true
    };
    
    // Add to API
    fetch('../api/add_lib_custom_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            department_id: window.DEPARTMENT_ID,
            year: currentAutoGenYear,
            ...customItem
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            customItem.custom_item_id = data.custom_item_id;
            autoGeneratedItems.push(customItem);
            displayAutoGeneratedItems();
            closeAddCustomItemModal();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding custom item');
    });
});

function editCustomItem(index) {
    const item = autoGeneratedItems[index];
    // Populate form with item data
    document.getElementById('customUACSCode').value = item.uacs_code;
    document.getElementById('customDescription').value = item.general_desc;
    document.getElementById('customTotalAmount').value = item.total_amount;
    document.getElementById('customQ1').value = item.quarter_1;
    document.getElementById('customQ2').value = item.quarter_2;
    document.getElementById('customQ3').value = item.quarter_3;
    document.getElementById('customQ4').value = item.quarter_4;
    
    // Store index for update
    document.getElementById('customItemForm').dataset.editIndex = index;
    showAddCustomItemModal();
}

function deleteCustomItem(index) {
    const item = autoGeneratedItems[index];
    
    if (!confirm('Are you sure you want to delete this custom item?')) {
        return;
    }
    
    if (item.custom_item_id) {
        fetch('../api/delete_lib_custom_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                custom_item_id: item.custom_item_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                autoGeneratedItems.splice(index, 1);
                displayAutoGeneratedItems();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting custom item');
        });
    } else {
        autoGeneratedItems.splice(index, 1);
        displayAutoGeneratedItems();
    }
}

function saveAutoGeneratedLIB() {
    // This would integrate with your existing LIB save functionality
    // You would convert autoGeneratedItems into the format expected by your create_lib.php endpoint
    alert('Save functionality to be integrated with existing LIB creation system');
}
```

## Usage Flow

1. **User clicks "Auto-Generate from Allocations"**
   - Modal opens with year selector

2. **User selects year and clicks "Generate LIB"**
   - System fetches all approved allocations for that department/year
   - System fetches any existing custom items
   - Displays combined list in table

3. **User can add custom items**
   - Click "Add Custom Item" button
   - Fill in UACS code, description, amounts
   - Item is saved to `lib_custom_items` table
   - Item appears in the list with "Custom" badge

4. **User can edit/delete custom items**
   - Only custom items can be edited/deleted
   - Allocation-derived items are read-only

5. **User saves the LIB**
   - All items (allocation + custom) are saved as a complete LIB

## Benefits

1. **Automatic Sync**: LIB items automatically reflect approved allocations
2. **Flexibility**: Departments can add custom items not in allocations
3. **Audit Trail**: Clear distinction between allocation-derived and custom items
4. **Quarterly Breakdown**: Supports quarterly budget distribution
5. **Soft Deletes**: Custom items can be recovered if needed

## Testing Checklist

- [ ] Install database table successfully
- [ ] Generate auto LIB from allocations
- [ ] Add custom item
- [ ] Edit custom item
- [ ] Delete custom item
- [ ] Save complete LIB with mixed items
- [ ] Verify allocation items are read-only
- [ ] Test with multiple years
- [ ] Test with department without allocations

## Future Enhancements

1. **Real-time Sync**: Auto-update LIB when allocations change
2. **Bulk Import**: Import multiple custom items from CSV
3. **Templates**: Save custom item templates for reuse
4. **Approval Workflow**: Require approval for custom items
5. **Variance Reports**: Compare LIB vs actual allocations
