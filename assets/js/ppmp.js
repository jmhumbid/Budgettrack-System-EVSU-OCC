// PPMP JavaScript Functions
let ppmpItemCounter = 0;
let currentPPMPType = 'ppmp'; // Track current PPMP type: 'ppmp' or 'supplemental'
let currentTab = 'ppmp'; // Track current active tab

// Helper function to format month (YYYY-MM to Month YYYY or empty if invalid)
function formatMonth(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00' || dateStr === 'null') {
        return '';
    }
    try {
        // Handle YYYY-MM format from month input
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
        console.error('Error formatting month:', e);
        return '';
    }
}

// Handle "Mark as Final" checkbox
function handleMarkAsFinal() {
    const markAsFinal = document.getElementById('markAsFinal');
    const isIndicative = document.getElementById('isIndicative');
    const isFinal = document.getElementById('isFinal');
    const saveButton = document.getElementById('savePPMPButton');
    
    if (markAsFinal.checked) {
        // If marked as final: set isFinal=1, isIndicative=0
        isFinal.value = '1';
        isIndicative.value = '0';
        // Update button text based on PPMP type
        if (saveButton) {
            const isSupplemental = currentPPMPType === 'supplemental';
            saveButton.textContent = isSupplemental ? 'Save Supplemental' : 'Save PPMP';
        }
    } else {
        // If not marked as final (draft): set isIndicative=1, isFinal=0
        isIndicative.value = '1';
        isFinal.value = '0';
        // Update button text
        if (saveButton) {
            saveButton.textContent = 'Save Draft';
        }
    }
}

// Profile dropdown toggle
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
    if (!button && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../auth/logout.php';
    }
}

// Modal functions
function showCreatePPMPModal(ppmpType = 'ppmp') {
    currentPPMPType = ppmpType;
    
    // Update precondition modal content based on type
    const intro = document.getElementById('preconditionIntro');
    const note = document.getElementById('preconditionNote');
    
    if (ppmpType === 'supplemental') {
        intro.textContent = 'Before creating a Supplemental PPMP, please ensure that:';
        note.textContent = 'Supplemental PPMPs are for additional procurement items not included in the original PPMP.';
    } else {
        intro.textContent = 'Before creating a PPMP, please ensure that:';
        note.textContent = 'Only proceed if proper planning and budget approval are in place.';
    }
    
    // Show custom precondition modal first
    document.getElementById('preconditionModal').classList.remove('hidden');
    
    // Close dropdown if open
    const dropdown = document.getElementById('createPPMPDropdown');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
}

function closePreconditionModal() {
    document.getElementById('preconditionModal').classList.add('hidden');
}

function confirmProceedToCreate() {
    closePreconditionModal();
    
    const modalTitle = currentPPMPType === 'supplemental' ? 'Create Supplemental PPMP' : 'Create PPMP';
    document.getElementById('modalTitle').textContent = modalTitle;
    document.getElementById('ppmpForm').reset();
    document.getElementById('ppmpId').value = '';
    document.getElementById('ppmpType').value = currentPPMPType;
    
    // Set fiscal year from the filter dropdown
    const yearFilter = document.getElementById('yearFilter');
    const selectedYear = yearFilter ? yearFilter.value : '2026';
    const fiscalYearToUse = selectedYear || '2026'; // Default to 2026 if "All Years" is selected
    
    document.getElementById('fiscalYear').value = fiscalYearToUse;
    document.getElementById('selectedFiscalYearDisplay').textContent = fiscalYearToUse;
    
    // Clear items container
    const container = document.getElementById('ppmpItemsContainer');
    if (container) {
        // Remove all item cards
        const itemCards = container.querySelectorAll('[id^="ppmpItem"]');
        itemCards.forEach(card => card.remove());
        
        // Show empty state
        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.classList.remove('hidden');
        }
    }
    
    ppmpItemCounter = 0;
    
    // Initialize button text to "Save Draft" since checkbox starts unchecked
    const saveButton = document.getElementById('savePPMPButton');
    if (saveButton) {
        saveButton.textContent = 'Save Draft';
    }
    
    document.getElementById('ppmpModal').classList.remove('hidden');
}

function closePPMPModal() {
    document.getElementById('ppmpModal').classList.add('hidden');
}

function showHistoryModal() {
    document.getElementById('historyModal').classList.remove('hidden');
    loadHistoryList();
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

function showDraftsModal() {
    document.getElementById('draftsModal').classList.remove('hidden');
    loadDraftsList();
}

function closeDraftsModal() {
    document.getElementById('draftsModal').classList.add('hidden');
}

// Add PPMP Item - Card-Based Layout
function addPPMPItem() {
    ppmpItemCounter++;
    const container = document.getElementById('ppmpItemsContainer');
    
    // Hide empty state
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.classList.add('hidden');
    }
    
    const itemCard = document.createElement('div');
    itemCard.id = `ppmpItem${ppmpItemCounter}`;
    itemCard.className = 'bg-white rounded-lg shadow-md border-2 border-gray-200 hover:border-purple-400 transition-all p-6';
    itemCard.innerHTML = `
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-3">
                <div class="bg-purple-100 text-purple-700 rounded-full w-10 h-10 flex items-center justify-center font-bold">
                    ${ppmpItemCounter}
                </div>
                <h5 class="text-lg font-bold text-gray-800">Item #${ppmpItemCounter}</h5>
            </div>
            <button type="button" onclick="removePPMPItem(${ppmpItemCounter})" 
                class="text-red-600 hover:bg-red-50 rounded-full p-2 transition-all" title="Remove Item">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Description -->
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <svg class="w-4 h-4 inline-block mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg>
                    General Description & Objective
                </label>
                <textarea name="general_description[]" rows="2" 
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none" 
                    placeholder="Describe the item and its purpose..."></textarea>
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                <select name="project_type[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 type-select">
                    <option value="">Select Type</option>
                    <option value="Goods">Goods</option>
                    <option value="Service">Service</option>
                    <option value="Infrastructure">Infrastructure</option>
                    <option value="custom">+ Custom</option>
                </select>
                <input type="text" name="type_custom[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mt-2 hidden" placeholder="Enter custom type">
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                <input type="number" step="1" name="quantity[]" 
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                    placeholder="0">
            </div>

            <!-- Unit -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit</label>
                <select name="unit[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 unit-select">
                    <option value="">Select Unit</option>
                    <option value="box">box</option>
                    <option value="boxes">boxes</option>
                    <option value="bottle">bottle</option>
                    <option value="bottles">bottles</option>
                    <option value="meter">meter</option>
                    <option value="meters">meters</option>
                    <option value="pack">pack</option>
                    <option value="packs">packs</option>
                    <option value="pad">pad</option>
                    <option value="pads">pads</option>
                    <option value="pcs">pcs</option>
                    <option value="ream">ream</option>
                    <option value="reams">reams</option>
                    <option value="roll">roll</option>
                    <option value="rolls">rolls</option>
                    <option value="room">room</option>
                    <option value="rooms">rooms</option>
                    <option value="set">set</option>
                    <option value="sets">sets</option>
                    <option value="unit">unit</option>
                    <option value="PAX">PAX</option>
                    <option value="custom">+ Custom</option>
                </select>
                <input type="text" name="unit_custom[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mt-2 hidden" placeholder="Enter custom unit">
            </div>

            <!-- Mode -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Recommended Mode of Procurement</label>
                <select name="recommended_mode[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 mode-select">
                    <option value="">Select Mode</option>
                    <option value="Agency to Agency">Agency to Agency</option>
                    <option value="Small Value Procurement">Small Value Procurement</option>
                    <option value="custom">+ Custom</option>
                </select>
                <input type="text" name="mode_custom[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mt-2 hidden" placeholder="Enter custom mode">
            </div>

            <!-- Pre-Procurement -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pre-Procurement Conference</label>
                <select name="pre_procurement[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="N">No</option>
                    <option value="Y">Yes</option>
                </select>
            </div>

            <!-- Dates -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start of Procurement</label>
                <input type="month" name="start_procurement[]" 
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End of Ads/Posting</label>
                <input type="month" name="end_ads_posting[]" 
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Expected Delivery</label>
                <input type="month" name="expected_delivery[]" 
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <!-- Source of Funds -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Source of Funds</label>
                <select name="source_of_funds[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Select Source</option>
                    <option value="IGF">Internally Generated Funds (IGF)</option>
                    <option value="RAF">Regular Agency Fund (RAF)</option>
                    <option value="BRF">Business Related Fund (BRF)</option>
                    <option value="TF">Trust Fund (TF)</option>
                    <option value="TR">Trust Receipts (TR)</option>
                </select>
            </div>

            <!-- Budget -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <svg class="w-4 h-4 inline-block mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Estimated Budget
                </label>
                <input type="number" step="0.01" name="estimated_budget[]" 
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                    placeholder="0.00">
            </div>

            <!-- LIB Expense Link -->
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <svg class="w-4 h-4 inline-block mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    Link to LIB Expense Category
                </label>
                <div class="lib-mapping-cell">
                    <button type="button" onclick="showLibExpenseSelector(${ppmpItemCounter})" 
                        class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 flex items-center justify-center gap-2 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        Link to LIB
                    </button>
                </div>
            </div>

            <!-- Hidden fields for allocated and remarks -->
            <input type="hidden" name="allocated_supporting[]" value="0">
            <input type="hidden" name="remarks[]" value="">
            
            <!-- Hidden fields for LIB mapping -->
            <input type="hidden" name="lib_category[]" value="">
            <input type="hidden" name="lib_particulars[]" value="">
            <input type="hidden" name="lib_account_code[]" value="">
        </div>
    `;
    
    container.appendChild(itemCard);
    
    // Add event listeners for custom inputs
    setupCustomInputs(itemCard);
    
    // Update item count and search visibility
    updateItemCount();
    
    // Scroll to the newly added item with smooth animation
    setTimeout(() => {
        itemCard.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center',
            inline: 'nearest'
        });
        
        // Add a brief highlight effect to draw attention
        itemCard.style.transition = 'all 0.3s ease';
        itemCard.style.transform = 'scale(1.02)';
        itemCard.style.boxShadow = '0 8px 16px rgba(128, 0, 0, 0.2)';
        
        // Remove highlight after animation
        setTimeout(() => {
            itemCard.style.transform = 'scale(1)';
            itemCard.style.boxShadow = '';
        }, 500);
    }, 100);
}

function setupCustomInputs(row) {
    // Type custom input
    const typeSelect = row.querySelector('.type-select');
    const typeCustom = row.querySelector('input[name="type_custom[]"]');
    
    if (typeSelect && typeCustom) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                typeCustom.classList.remove('hidden');
                typeCustom.required = true;
            } else {
                typeCustom.classList.add('hidden');
                typeCustom.required = false;
                typeCustom.value = '';
            }
        });
    }
    
    // Unit custom input
    const unitSelect = row.querySelector('.unit-select');
    const unitCustom = row.querySelector('input[name="unit_custom[]"]');
    
    unitSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            unitCustom.classList.remove('hidden');
            unitCustom.required = true;
        } else {
            unitCustom.classList.add('hidden');
            unitCustom.required = false;
            unitCustom.value = '';
        }
    });
    
    // Mode custom input
    const modeSelect = row.querySelector('.mode-select');
    const modeCustom = row.querySelector('input[name="mode_custom[]"]');
    
    modeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            modeCustom.classList.remove('hidden');
            modeCustom.required = true;
        } else {
            modeCustom.classList.add('hidden');
            modeCustom.required = false;
            modeCustom.value = '';
        }
    });
}

function removePPMPItem(itemId) {
    const card = document.getElementById(`ppmpItem${itemId}`);
    if (card) {
        card.remove();
        
        // Check if container is empty
        const container = document.getElementById('ppmpItemsContainer');
        const remainingCards = container.querySelectorAll('[id^="ppmpItem"]');
        
        if (remainingCards.length === 0) {
            // Show empty state
            const emptyState = document.getElementById('emptyState');
            if (emptyState) {
                emptyState.classList.remove('hidden');
            }
        }
        
        // Update item count and search visibility
        updateItemCount();
    }
}

// Update item count badge and search bar visibility
function updateItemCount() {
    const container = document.getElementById('ppmpItemsContainer');
    const items = container.querySelectorAll('[id^="ppmpItem"]');
    const count = items.length;
    
    const badge = document.getElementById('itemCountBadge');
    const searchContainer = document.getElementById('itemSearchContainer');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = `${count} item${count !== 1 ? 's' : ''}`;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
    
    // Show search bar when there are 5 or more items
    if (searchContainer) {
        if (count >= 5) {
            searchContainer.classList.remove('hidden');
        } else {
            searchContainer.classList.add('hidden');
            clearItemSearch(); // Clear search when hiding
        }
    }
}

// Search PPMP items
function searchPPMPItems() {
    const searchInput = document.getElementById('itemSearchInput');
    const searchTerm = searchInput.value.toLowerCase().trim();
    const container = document.getElementById('ppmpItemsContainer');
    const items = container.querySelectorAll('[id^="ppmpItem"]');
    const clearBtn = document.getElementById('clearSearchBtn');
    const resultsInfo = document.getElementById('searchResultsInfo');
    
    // Show/hide clear button
    if (clearBtn) {
        if (searchTerm) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }
    
    if (!searchTerm) {
        // Show all items
        items.forEach(item => {
            item.classList.remove('hidden', 'ring-4', 'ring-purple-400', 'ring-opacity-50');
        });
        if (resultsInfo) {
            resultsInfo.classList.add('hidden');
        }
        return;
    }
    
    let matchCount = 0;
    let firstMatch = null;
    
    items.forEach(item => {
        // Get searchable content from the item
        const description = item.querySelector('textarea[name="general_description[]"]')?.value.toLowerCase() || '';
        const type = item.querySelector('select[name="project_type[]"]')?.value.toLowerCase() || '';
        const typeCustom = item.querySelector('input[name="type_custom[]"]')?.value.toLowerCase() || '';
        const unit = item.querySelector('select[name="unit[]"]')?.value.toLowerCase() || '';
        const unitCustom = item.querySelector('input[name="unit_custom[]"]')?.value.toLowerCase() || '';
        const mode = item.querySelector('select[name="recommended_mode[]"]')?.value.toLowerCase() || '';
        const modeCustom = item.querySelector('input[name="mode_custom[]"]')?.value.toLowerCase() || '';
        const budget = item.querySelector('input[name="estimated_budget[]"]')?.value || '';
        const source = item.querySelector('select[name="source_of_funds[]"]')?.value.toLowerCase() || '';
        
        // Check if any field matches the search term
        const matches = description.includes(searchTerm) ||
                       type.includes(searchTerm) ||
                       typeCustom.includes(searchTerm) ||
                       unit.includes(searchTerm) ||
                       unitCustom.includes(searchTerm) ||
                       mode.includes(searchTerm) ||
                       modeCustom.includes(searchTerm) ||
                       budget.includes(searchTerm) ||
                       source.includes(searchTerm);
        
        if (matches) {
            item.classList.remove('hidden');
            item.classList.add('ring-4', 'ring-purple-400', 'ring-opacity-50');
            matchCount++;
            if (!firstMatch) {
                firstMatch = item;
            }
        } else {
            item.classList.add('hidden');
            item.classList.remove('ring-4', 'ring-purple-400', 'ring-opacity-50');
        }
    });
    
    // Update results info
    if (resultsInfo) {
        if (matchCount > 0) {
            resultsInfo.textContent = `Found ${matchCount} matching item${matchCount !== 1 ? 's' : ''}`;
            resultsInfo.classList.remove('hidden', 'text-red-600');
            resultsInfo.classList.add('text-purple-600');
            
            // Scroll to first match
            if (firstMatch) {
                firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            resultsInfo.textContent = 'No items found';
            resultsInfo.classList.remove('hidden', 'text-purple-600');
            resultsInfo.classList.add('text-red-600');
        }
    }
}

// Clear item search
function clearItemSearch() {
    const searchInput = document.getElementById('itemSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const resultsInfo = document.getElementById('searchResultsInfo');
    const container = document.getElementById('ppmpItemsContainer');
    const items = container.querySelectorAll('[id^="ppmpItem"]');
    
    if (searchInput) {
        searchInput.value = '';
    }
    
    if (clearBtn) {
        clearBtn.classList.add('hidden');
    }
    
    if (resultsInfo) {
        resultsInfo.classList.add('hidden');
    }
    
    // Show all items and remove highlighting
    items.forEach(item => {
        item.classList.remove('hidden', 'ring-4', 'ring-purple-400', 'ring-opacity-50');
    });
}


// Save PPMP
function savePPMP() {
    console.log('savePPMP called');
    
    const form = document.getElementById('ppmpForm');
    if (!form) {
        console.error('Form not found!');
        alert('Error: Form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Log form data for debugging
    console.log('Form data entries:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Ensure the hidden fields are properly set based on markAsFinal
    handleMarkAsFinal();
    
    // Convert month inputs (YYYY-MM) to date format (YYYY-MM-01) for database
    const startProcurements = formData.getAll('start_procurement[]');
    const endAdsPostings = formData.getAll('end_ads_posting[]');
    const expectedDeliveries = formData.getAll('expected_delivery[]');
    
    formData.delete('start_procurement[]');
    formData.delete('end_ads_posting[]');
    formData.delete('expected_delivery[]');
    
    startProcurements.forEach(date => {
        // Convert YYYY-MM to YYYY-MM-01 for database
        const formattedDate = date ? `${date}-01` : '';
        formData.append('start_procurement[]', formattedDate);
    });
    
    endAdsPostings.forEach(date => {
        const formattedDate = date ? `${date}-01` : '';
        formData.append('end_ads_posting[]', formattedDate);
    });
    
    expectedDeliveries.forEach(date => {
        const formattedDate = date ? `${date}-01` : '';
        formData.append('expected_delivery[]', formattedDate);
    });
    
    // Process custom units and modes
    const types = formData.getAll('project_type[]');
    const typeCustoms = formData.getAll('type_custom[]');
    const units = formData.getAll('unit[]');
    const unitCustoms = formData.getAll('unit_custom[]');
    const modes = formData.getAll('recommended_mode[]');
    const modeCustoms = formData.getAll('mode_custom[]');
    
    // Replace custom values
    formData.delete('project_type[]');
    formData.delete('unit[]');
    formData.delete('recommended_mode[]');
    
    types.forEach((type, index) => {
        const finalType = type === 'custom' ? typeCustoms[index] : type;
        formData.append('project_type[]', finalType);
    });
    
    units.forEach((unit, index) => {
        const finalUnit = unit === 'custom' ? unitCustoms[index] : unit;
        formData.append('unit[]', finalUnit);
    });
    
    modes.forEach((mode, index) => {
        const finalMode = mode === 'custom' ? modeCustoms[index] : mode;
        formData.append('recommended_mode[]', finalMode);
    });
    
    const ppmpId = document.getElementById('ppmpId').value;
    const url = ppmpId ? '../api/update_ppmp.php' : '../api/create_ppmp.php';
    
    console.log('Submitting to:', url);
    console.log('PPMP ID:', ppmpId);
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Show success message with LIB sync info
            let message = data.message;
            if (data.lib_synced && data.lib_id) {
                message += '\n\n✅ Items have been automatically added to the Line Item Budget (LIB).\nGo to LIB page to view them.';
            }
            alert(message);
            closePPMPModal();
            
            // Reload the page to show the newly created/updated PPMP
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        console.error('Error stack:', error.stack);
        alert('An error occurred while saving the PPMP: ' + error.message);
    });
}

// Load PPMP list
function loadPPMPList(filterYear = null) {
    const departmentId = window.DEPARTMENT_ID || '';
    let url = `../api/get_ppmp_list.php${departmentId ? '?department_id=' + departmentId : ''}`;
    
    // Add year filter if provided
    if (filterYear) {
        url += (departmentId ? '&' : '?') + 'fiscal_year=' + filterYear;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.ppmps.length > 0) {
                    displayCurrentPPMP(data.ppmps[0].id);
                } else {
                    // Show empty state
                    const yearText = filterYear ? ` for ${filterYear}` : '';
                    const container = document.getElementById('currentPPMPContainer');
                    if (container) {
                        container.innerHTML = `
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-lg font-semibold mb-2">No PPMP Found${yearText}</p>
                                <p class="text-sm">Create your first PPMP to get started</p>
                            </div>
                        `;
                    }
                }
            } else {
                console.error('Error loading PPMP records:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Filter PPMP by year
function filterPPMPByYear() {
    const yearFilter = document.getElementById('yearFilter').value;
    const currentTab = localStorage.getItem('activePPMPTab') || 'ppmp';
    
    // Reload the current tab with year filter
    if (currentTab === 'ppmp') {
        loadCurrentPPMP('ppmp', true, yearFilter || null);
    } else if (currentTab === 'supplemental') {
        loadCurrentPPMP('supplemental', true, yearFilter || null);
    }
}

// Global variables for pagination
let currentPPMPPage = 1;
let currentPPMPData = null;

function displayCurrentPPMP(ppmpId, ppmpType = 'ppmp') {
    fetch(`../api/get_ppmp_details.php?id=${ppmpId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentPPMPData = data;
                currentPPMPPage = 1;
                
                // Determine which container to use based on ppmp_type from data
                const actualType = data.ppmp.ppmp_type || ppmpType;
                const containerId = actualType === 'supplemental' ? 'currentSupplementalContainer' : 'currentPPMPContainer';
                const container = document.getElementById(containerId);
                
                if (container) {
                    container.innerHTML = generatePPMPView(data.ppmp, data.items, data.department, 1);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function changePPMPPage(page) {
    if (currentPPMPData) {
        currentPPMPPage = page;
        
        // Determine which container to use based on ppmp_type
        const ppmpType = currentPPMPData.ppmp.ppmp_type || 'ppmp';
        const containerId = ppmpType === 'supplemental' ? 'currentSupplementalContainer' : 'currentPPMPContainer';
        const container = document.getElementById(containerId);
        
        if (container) {
            container.innerHTML = generatePPMPView(currentPPMPData.ppmp, currentPPMPData.items, currentPPMPData.department, page);
            // Scroll to top of table
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

function generatePPMPView(ppmp, items, department, currentPage = 1) {
    const itemsPerPage = 20;
    const totalPages = Math.ceil(items.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedItems = items.slice(startIndex, endIndex);
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    const statusLabels = {
        'draft': 'DRAFT',
        'approved': 'FINAL',
        'rejected': 'REJECTED'
    };
    const statusClass = statusColors[ppmp.status] || 'bg-gray-100 text-gray-800';
    const statusText = statusLabels[ppmp.status] || ppmp.status.toUpperCase();
    
    // Determine title based on ppmp_type
    const isSupplemental = ppmp.ppmp_type === 'supplemental';
    const screenTitle = isSupplemental ? 'SUPPLEMENTAL (PPMP)' : 'Project Procurement Management Plan (PPMP)';
    const printTitle = isSupplemental ? 'SUPPLEMENTAL (PPMP)' : 'PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP)';
    
    // Get current date and time for footer
    const now = new Date();
    const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: true };
    const generatedDate = now.toLocaleDateString('en-US', dateOptions);
    const generatedTime = now.toLocaleTimeString('en-US', timeOptions);
    
    let html = `
        <!-- Screen View Header (Hidden in Print) -->
        <div class="screen-only-header mb-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <!-- Header Bar -->
                <div class="bg-gradient-to-r from-maroon to-red-700 px-6 py-4">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        ${screenTitle}
                    </h2>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Department -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Department</p>
                                <p class="text-base font-bold text-gray-900">${department.dept_name}</p>
                            </div>
                        </div>
                        
                        <!-- Fiscal Year -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fiscal Year</p>
                                <p class="text-base font-bold text-gray-900">${ppmp.fiscal_year}</p>
                            </div>
                        </div>
                        
                        <!-- PPMP Number -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">${isSupplemental ? 'Supplemental Number' : 'PPMP Number'}</p>
                                <p class="text-base font-bold text-gray-900">${ppmp.ppmp_number}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="mt-4 flex justify-end">
                        <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Print Only Header (Hidden on Screen) -->
        <div class="print-only-header text-center mb-6" style="display: none; page-break-inside: avoid;">
            <h1 class="text-xl font-bold mb-1" style="color: #800000; letter-spacing: 0.5px;">EASTERN VISAYAS STATE UNIVERSITY</h1>
            <h2 class="text-lg font-semibold mb-1" style="color: #2d3748;">ORMOC CAMPUS</h2>
            <p class="text-sm mb-2" style="color: #718096;">Ormoc City</p>
            
            <h3 class="text-lg font-bold mt-4 mb-2" style="color: #2d3748;">${printTitle}</h3>
            <h4 class="text-base font-bold mb-1" style="color: #2d3748;">${department.dept_name}</h4>
            <p class="text-sm mb-1" style="color: #2d3748;">Fiscal Year: ${ppmp.fiscal_year} | PPMP No: ${ppmp.ppmp_number}</p>
            <p class="text-sm mb-2" style="color: #2d3748;">
                ${ppmp.is_indicative ? 'INDICATIVE' : ''} ${ppmp.is_final ? 'FINAL' : ''} - ${statusText}
            </p>
        </div>
        
        <!-- Action Buttons (Hidden in Print) -->
        <div class="flex gap-2 mb-4 no-print flex-wrap">
            ${ppmp.status === 'draft' ? `
                <button onclick="editPPMP(${ppmp.id})" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </button>
                <button onclick="deletePPMP(${ppmp.id})" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            ` : ''}
            <button onclick="expandAllPPMPDetails()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                Expand All
            </button>
            <button onclick="collapseAllPPMPDetails()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
                Collapse All
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
        </div>
        
        <!-- PPMP Table -->
        <div class="overflow-x-auto">
            <table class="w-full ppmp-table border-collapse border border-gray-300" style="width: 100%;">
                <thead>
                    <!-- Screen View Header (3 columns) -->
                    <tr class="screen-only-row">
                        <th class="border border-gray-300" style="width: 5%;">#</th>
                        <th class="border border-gray-300" style="width: 70%;">General Description & Objective</th>
                        <th class="border border-gray-300" style="width: 25%;">Budget</th>
                    </tr>
                    <!-- Print View Header (12 columns - old format without Allocated & Remarks) -->
                    <tr class="print-only-row" style="display: none;">
                        <th class="border border-gray-300">#</th>
                        <th class="border border-gray-300">General Description & Objective</th>
                        <th class="border border-gray-300">Type</th>
                        <th class="border border-gray-300">Qty</th>
                        <th class="border border-gray-300">Unit</th>
                        <th class="border border-gray-300">Recommended Mode</th>
                        <th class="border border-gray-300">Pre-Proc</th>
                        <th class="border border-gray-300">Start</th>
                        <th class="border border-gray-300">End Ads</th>
                        <th class="border border-gray-300">Delivery</th>
                        <th class="border border-gray-300">Source</th>
                        <th class="border border-gray-300">Budget</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    // Screen view: show paginated items with collapsible details
    paginatedItems.forEach((item, index) => {
        const globalIndex = startIndex + index + 1;
        const remarksText = item.deducted_from_categories || item.remarks || '';
        let remarksHtml = '';
        if (remarksText) {
            const targetPage = window.IS_BUDGET ? '../pages/utilization.php' : '../pages/utilization__view.php';
            remarksHtml = remarksText.split(',').map(r => r.trim()).filter(Boolean).map(r =>
                '<a href="' + targetPage + '?highlight=' + encodeURIComponent(r) + '" style="color:inherit;text-decoration:none;cursor:pointer;" title="View in Utilization">' + r + '</a>'
            ).join(', ');
        }
        
        // Main row (collapsed view)
        html += `
            <tr class="screen-only-row hover:bg-gray-50 cursor-pointer" onclick="togglePPMPDetails(${globalIndex})">
                <td class="border border-gray-300 text-center font-semibold text-gray-700">${globalIndex}</td>
                <td class="border border-gray-300">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">${item.general_description}</span>
                        <svg id="arrow-${globalIndex}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </td>
                <td class="border border-gray-300 text-right font-semibold text-green-700">₱${parseFloat(item.estimated_budget).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            </tr>
        `;
        
        // Details row (expanded view - hidden by default)
        html += `
            <tr id="details-${globalIndex}" class="screen-only-row hidden bg-gray-50">
                <td colspan="3" class="border border-gray-300 p-0">
                    <div class="px-2 py-1.5 bg-gradient-to-r from-blue-50 to-purple-50">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-1.5">
                            <div class="bg-white rounded px-2 py-1 shadow-sm">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Type</p>
                                <p class="text-xs font-bold text-gray-800">${item.project_type}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Quantity</p>
                                <p class="text-xs font-bold text-gray-800">${parseInt(item.quantity)} ${item.unit}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Pre-Proc</p>
                                <p class="text-xs font-bold text-gray-800">${item.pre_procurement_conference === 'Y' ? 'Yes' : 'No'}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Source</p>
                                <p class="text-xs font-bold text-gray-800">${item.source_of_funds}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm col-span-2">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Recommended Mode</p>
                                <p class="text-xs font-bold text-gray-800">${item.recommended_mode}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Start Procurement</p>
                                <p class="text-xs font-bold text-gray-800">${formatMonth(item.start_procurement) || 'N/A'}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">End Ads/Posting</p>
                                <p class="text-xs font-bold text-gray-800">${formatMonth(item.end_ads_posting) || 'N/A'}</p>
                            </div>
                            <div class="bg-white rounded px-2 py-1 shadow-sm col-span-2 md:col-span-2">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase">Expected Delivery</p>
                                <p class="text-xs font-bold text-gray-800">${formatMonth(item.expected_delivery) || 'N/A'}</p>
                            </div>
                            ${item.lib_category ? `
                            <div class="bg-green-50 border-2 border-green-300 rounded px-2 py-1 shadow-sm col-span-2">
                                <p class="text-[10px] font-semibold text-green-700 uppercase mb-0.5">🔗 Linked to LIB Category</p>
                                <p class="text-xs font-bold text-green-900">${item.lib_category}</p>
                                <p class="text-[10px] text-green-700 mt-0.5">${item.lib_particulars || ''}</p>
                                ${item.lib_account_code ? `<p class="text-[10px] text-green-600 font-mono">UACS: ${item.lib_account_code}</p>` : ''}
                            </div>
                            ` : ''}
                            ${remarksText ? `
                            <div class="bg-yellow-50 border-2 border-yellow-300 rounded px-2 py-1 shadow-sm col-span-2 md:col-span-4">
                                <p class="text-[10px] font-semibold text-yellow-700 uppercase mb-0.5">📝 Obligated</p>
                                <p class="text-xs font-bold text-yellow-900">${remarksHtml}</p>
                            </div>
                            ` : ''}
                            ${parseFloat(item.allocated_supporting_funds) > 0 ? `
                            <div class="bg-blue-50 border-2 border-blue-300 rounded px-2 py-1 shadow-sm col-span-2 md:col-span-4">
                                <p class="text-[10px] font-semibold text-blue-700 uppercase mb-0.5">💰 Allocated Supporting Funds</p>
                                <p class="text-xs font-bold text-blue-900">₱${parseFloat(item.allocated_supporting_funds).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    // Print view: show ALL items in traditional format (hidden on screen)
    items.forEach((item, index) => {
        html += `
            <tr class="print-only-row" style="display: none;">
                <td class="border border-gray-300 text-center">${index + 1}</td>
                <td class="border border-gray-300">${item.general_description}</td>
                <td class="border border-gray-300 text-center">${item.project_type}</td>
                <td class="border border-gray-300 text-right">${parseInt(item.quantity)}</td>
                <td class="border border-gray-300 text-center">${item.unit}</td>
                <td class="border border-gray-300">${item.recommended_mode}</td>
                <td class="border border-gray-300 text-center">${item.pre_procurement_conference}</td>
                <td class="border border-gray-300 text-center">${formatMonth(item.start_procurement)}</td>
                <td class="border border-gray-300 text-center">${formatMonth(item.end_ads_posting)}</td>
                <td class="border border-gray-300 text-center">${formatMonth(item.expected_delivery)}</td>
                <td class="border border-gray-300 text-center">${item.source_of_funds}</td>
                <td class="border border-gray-300 text-right">₱${parseFloat(item.estimated_budget).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            </tr>
        `;
    });
    
    // Calculate page totals
    let pageTotalEstimatedBudget = 0;
    paginatedItems.forEach(item => {
        pageTotalEstimatedBudget += parseFloat(item.estimated_budget);
    });
    
    // Calculate grand totals
    let totalEstimatedBudget = 0;
    items.forEach(item => {
        totalEstimatedBudget += parseFloat(item.estimated_budget);
    });
    
    // Add page subtotal if multiple pages (screen only)
    if (totalPages > 1) {
        html += `
            <tr class="bg-gray-100 no-print">
                <td class="border border-gray-300 text-right font-semibold" colspan="2">Page ${currentPage} Subtotal:</td>
                <td class="border border-gray-300 text-right font-semibold">₱${pageTotalEstimatedBudget.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            </tr>
        `;
    }
    
    // Add grand total row for screen view (3 columns)
    html += `
        <tr class="total-row screen-only-row">
            <td class="border border-gray-300 text-right font-bold" colspan="2">GRAND TOTAL:</td>
            <td class="border border-gray-300 text-right font-bold">₱${totalEstimatedBudget.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
        </tr>
    `;
    
    // Add grand total row for print view (12 columns)
    html += `
        <tr class="total-row print-only-row" style="display: none;">
            <td class="border border-gray-300 text-right font-bold" colspan="11">GRAND TOTAL:</td>
            <td class="border border-gray-300 text-right font-bold">₱${totalEstimatedBudget.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
        </tr>
    `;
    
    html += `
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls (Hidden in Print) -->
        ${totalPages > 1 ? `
        <div class="flex justify-between items-center mt-4 no-print">
            <div class="text-sm text-gray-600">
                Showing items ${startIndex + 1} to ${Math.min(endIndex, items.length)} of ${items.length} total
            </div>
            <div class="flex gap-2 items-center">
                <button 
                    onclick="changePPMPPage(${currentPage - 1})" 
                    ${currentPage === 1 ? 'disabled' : ''}
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Previous
                </button>
                <span class="px-4 py-2 bg-maroon text-white rounded-lg font-semibold">
                    Page ${currentPage} of ${totalPages}
                </span>
                <button 
                    onclick="changePPMPPage(${currentPage + 1})" 
                    ${currentPage === totalPages ? 'disabled' : ''}
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2 transition-colors">
                    Next
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>
        ` : ''}
        
        <!-- Print Footer (Hidden on Screen) -->
        <div class="print-footer mt-6 text-center" style="display: none; page-break-inside: avoid;">
            <p class="text-xs" style="color: #718096;">Generated on ${generatedDate} at ${generatedTime}</p>
            <p class="text-xs" style="color: #718096;">BudgetTrack System - EVSU Ormoc Campus</p>
            <p class="text-xs page-number" style="color: #718096; margin-top: 4px;">Page <span class="current-page"></span> of <span class="total-pages">${Math.ceil(items.length / 20)}</span></p>
        </div>
    `;
    
    return html;
}

function loadHistoryList() {
    const departmentId = window.DEPARTMENT_ID || '';
    fetch(`../api/get_ppmp_list.php${departmentId ? '?department_id=' + departmentId : ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Filter only approved (final) records for history
                const finalPPMPs = data.ppmps.filter(ppmp => ppmp.status === 'approved');
                displayHistoryList(finalPPMPs);
            } else {
                document.getElementById('historyListContainer').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p>Error loading history: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function loadDraftsList() {
    const departmentId = window.DEPARTMENT_ID || '';
    fetch(`../api/get_ppmp_list.php${departmentId ? '?department_id=' + departmentId : ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Filter only draft records
                const draftPPMPs = data.ppmps.filter(ppmp => ppmp.status === 'draft');
                // Store all drafts globally for filtering
                window.allDrafts = draftPPMPs;
                // Display with current filter
                filterDrafts();
            } else {
                document.getElementById('draftsListContainer').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p>Error loading drafts: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Filter drafts by type
function filterDrafts() {
    const filterValue = document.getElementById('draftTypeFilter').value;
    let filteredDrafts = window.allDrafts || [];
    
    if (filterValue === 'ppmp') {
        filteredDrafts = filteredDrafts.filter(ppmp => ppmp.ppmp_type === 'ppmp' || !ppmp.ppmp_type);
    } else if (filterValue === 'supplemental') {
        filteredDrafts = filteredDrafts.filter(ppmp => ppmp.ppmp_type === 'supplemental');
    }
    
    displayDraftsList(filteredDrafts);
}

function displayHistoryList(ppmps) {
    const container = document.getElementById('historyListContainer');
    if (ppmps.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <p>No PPMP records found.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="overflow-x-auto"><table class="w-full ppmp-table"><thead><tr>';
    html += '<th>PPMP Number</th><th>Fiscal Year</th><th>Type</th><th>Status</th><th>Created Date</th><th>Actions</th>';
    html += '</tr></thead><tbody>';

    ppmps.forEach(ppmp => {
        const statusColors = {
            'draft': 'bg-gray-100 text-gray-800',
            'approved': 'bg-green-100 text-green-800',
            'rejected': 'bg-red-100 text-red-800'
        };
        const statusLabels = {
            'draft': 'DRAFT',
            'approved': 'FINAL',
            'rejected': 'REJECTED'
        };
        const statusClass = statusColors[ppmp.status] || 'bg-gray-100 text-gray-800';
        const statusText = statusLabels[ppmp.status] || ppmp.status.toUpperCase();
        
        // Determine PPMP type badge
        const ppmpType = ppmp.ppmp_type || 'ppmp';
        const typeBadge = ppmpType === 'supplemental' 
            ? '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Supplemental</span>'
            : '<span class="px-2 py-1 bg-maroon bg-opacity-10 text-maroon rounded-full text-xs font-semibold">PPMP</span>';
        
        html += `<tr>
            <td class="font-semibold">${ppmp.ppmp_number}</td>
            <td>${ppmp.fiscal_year}</td>
            <td>${typeBadge}</td>
            <td><span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusText}</span></td>
            <td>${new Date(ppmp.created_at).toLocaleDateString()}</td>
            <td>
                <div class="flex gap-2">
                    <button onclick="viewPPMPFromHistory(${ppmp.id})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">View</button>
                    <button onclick="downloadPPMPFromHistory(${ppmp.id})" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">Download</button>
                    <button onclick="deletePPMPFromHistory(${ppmp.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm">Delete</button>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function displayDraftsList(ppmps) {
    const container = document.getElementById('draftsListContainer');
    if (ppmps.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <p>No draft PPMP records found.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="overflow-x-auto"><table class="w-full ppmp-table"><thead><tr>';
    html += '<th>PPMP Number</th><th>Fiscal Year</th><th>Type</th><th>Created Date</th><th>Actions</th>';
    html += '</tr></thead><tbody>';

    ppmps.forEach(ppmp => {
        // Determine PPMP type badge
        const ppmpType = ppmp.ppmp_type || 'ppmp';
        const typeBadge = ppmpType === 'supplemental' 
            ? '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">Supplemental</span>'
            : '<span class="px-2 py-1 bg-maroon bg-opacity-10 text-maroon rounded-full text-xs font-semibold">PPMP</span>';
        
        html += `<tr>
            <td class="font-semibold">${ppmp.ppmp_number}</td>
            <td>${ppmp.fiscal_year}</td>
            <td>${typeBadge}</td>
            <td>${new Date(ppmp.created_at).toLocaleDateString()}</td>
            <td>
                <div class="flex gap-2">
                    <button onclick="viewPPMPFromDrafts(${ppmp.id})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">View</button>
                    <button onclick="editPPMPFromDrafts(${ppmp.id})" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">Edit</button>
                    <button onclick="deletePPMPFromDrafts(${ppmp.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm">Delete</button>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function viewPPMPFromDrafts(id) {
    closeDraftsModal();
    
    // Fetch PPMP details to check type
    fetch(`../api/get_ppmp_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ppmp) {
                const ppmpType = data.ppmp.ppmp_type || 'ppmp';
                
                // Show supplemental tab if it's a supplemental PPMP
                if (ppmpType === 'supplemental') {
                    const suppTab = document.getElementById('ppmpTab-supplemental');
                    if (suppTab) {
                        suppTab.classList.remove('hidden');
                    }
                    // Switch to supplemental tab
                    switchPPMPTab('supplemental');
                } else {
                    // Switch to PPMP tab
                    switchPPMPTab('ppmp');
                }
                // Display the PPMP with correct type
                displayCurrentPPMP(id, ppmpType);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback: just display it
            displayCurrentPPMP(id, 'ppmp');
        });
}

function editPPMPFromDrafts(id) {
    closeDraftsModal();
    editPPMP(id);
}

function deletePPMPFromDrafts(id) {
    if (!confirm('Are you sure you want to delete this draft PPMP? This action cannot be undone.')) {
        return;
    }
    
    fetch('../api/delete_ppmp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Reload drafts list AND refresh main container
            loadDraftsList();
            loadCurrentPPMP(currentTab, true);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the PPMP');
    });
}

function viewPPMPFromHistory(id) {
    closeHistoryModal();
    
    // Fetch PPMP details to check type
    fetch(`../api/get_ppmp_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ppmp) {
                const ppmpType = data.ppmp.ppmp_type || 'ppmp';
                
                // Show supplemental tab if it's a supplemental PPMP
                if (ppmpType === 'supplemental') {
                    const suppTab = document.getElementById('ppmpTab-supplemental');
                    if (suppTab) {
                        suppTab.classList.remove('hidden');
                    }
                    // Switch to supplemental tab
                    switchPPMPTab('supplemental');
                } else {
                    // Switch to PPMP tab
                    switchPPMPTab('ppmp');
                }
                // Display the PPMP with correct type
                displayCurrentPPMP(id, ppmpType);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback: just display it
            displayCurrentPPMP(id, 'ppmp');
        });
}

function downloadPPMPFromHistory(id) {
    // Fetch PPMP details to check type
    fetch(`../api/get_ppmp_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ppmp) {
                const ppmpType = data.ppmp.ppmp_type || 'ppmp';
                
                // Show supplemental tab if it's a supplemental PPMP
                if (ppmpType === 'supplemental') {
                    const suppTab = document.getElementById('ppmpTab-supplemental');
                    if (suppTab) {
                        suppTab.classList.remove('hidden');
                    }
                    // Switch to supplemental tab
                    switchPPMPTab('supplemental');
                } else {
                    // Switch to PPMP tab
                    switchPPMPTab('ppmp');
                }
                // Display the PPMP with correct type
                displayCurrentPPMP(id, ppmpType);
                closeHistoryModal();
                // Wait a moment for the content to load, then print
                setTimeout(() => {
                    window.print();
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback
            displayCurrentPPMP(id, 'ppmp');
            closeHistoryModal();
            setTimeout(() => {
                window.print();
            }, 500);
        });
}

function deletePPMPFromHistory(id) {
    if (!confirm('Are you sure you want to delete this PPMP? This action cannot be undone.')) {
        return;
    }
    
    fetch('../api/delete_ppmp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Reload history list AND refresh main container
            loadHistoryList();
            loadCurrentPPMP(currentTab, true);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the PPMP');
    });
}

function editPPMP(id) {
    console.log('editPPMP called with id:', id);
    fetch(`../api/get_ppmp_details.php?id=${id}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Set currentPPMPType based on the PPMP being edited
                currentPPMPType = data.ppmp.ppmp_type || 'ppmp';
                console.log('Current PPMP Type:', currentPPMPType);
                
                // Set modal title based on type
                const modalTitle = currentPPMPType === 'supplemental' ? 'Edit Supplemental PPMP' : 'Edit PPMP';
                const modalTitleEl = document.getElementById('modalTitle');
                if (modalTitleEl) {
                    modalTitleEl.textContent = modalTitle;
                    console.log('Modal title set to:', modalTitle);
                } else {
                    console.warn('modalTitle element not found');
                }
                
                const ppmpIdEl = document.getElementById('ppmpId');
                if (ppmpIdEl) {
                    ppmpIdEl.value = data.ppmp.id;
                    console.log('ppmpId set to:', data.ppmp.id);
                } else {
                    console.warn('ppmpId element not found');
                }
                
                const ppmpTypeEl = document.getElementById('ppmpType');
                if (ppmpTypeEl) {
                    ppmpTypeEl.value = currentPPMPType;
                    console.log('ppmpType set to:', currentPPMPType);
                } else {
                    console.warn('ppmpType element not found');
                }
                
                const fiscalYearEl = document.getElementById('fiscalYear');
                if (fiscalYearEl) {
                    fiscalYearEl.value = data.ppmp.fiscal_year;
                    console.log('fiscalYear set to:', data.ppmp.fiscal_year);
                } else {
                    console.warn('fiscalYear element not found');
                }
                
                const ppmpNumberEl = document.getElementById('ppmpNumber');
                if (ppmpNumberEl) {
                    ppmpNumberEl.value = data.ppmp.ppmp_number;
                    console.log('ppmpNumber set to:', data.ppmp.ppmp_number);
                } else {
                    console.log('ppmpNumber element not found (this is OK if auto-generated)');
                }
                
                // Set "Mark as Final" checkbox based on status
                const isFinalStatus = data.ppmp.status === 'approved';
                const markAsFinalEl = document.getElementById('markAsFinal');
                if (markAsFinalEl) {
                    markAsFinalEl.checked = isFinalStatus;
                    console.log('markAsFinal set to:', isFinalStatus);
                } else {
                    console.warn('markAsFinal element not found');
                }
                
                // Set hidden fields based on the data
                const isIndicativeEl = document.getElementById('isIndicative');
                if (isIndicativeEl) {
                    isIndicativeEl.value = data.ppmp.is_indicative == 1 ? '1' : '0';
                    console.log('isIndicative set to:', isIndicativeEl.value);
                } else {
                    console.warn('isIndicative element not found');
                }
                
                const isFinalEl = document.getElementById('isFinal');
                if (isFinalEl) {
                    isFinalEl.value = data.ppmp.is_final == 1 ? '1' : '0';
                    console.log('isFinal set to:', isFinalEl.value);
                } else {
                    console.warn('isFinal element not found');
                }
                
                // Update button text based on status and type
                const saveButton = document.getElementById('savePPMPButton');
                if (saveButton) {
                    if (isFinalStatus) {
                        saveButton.textContent = currentPPMPType === 'supplemental' ? 'Save Supplemental' : 'Save PPMP';
                    } else {
                        saveButton.textContent = 'Save Draft';
                    }
                }
                
                // Clear existing items - use card container instead of table
                const container = document.getElementById('ppmpItemsContainer');
                if (container) {
                    // Remove all item cards
                    const itemCards = container.querySelectorAll('[id^="ppmpItem"]');
                    itemCards.forEach(card => card.remove());
                }
                
                // Hide empty state if it exists
                const emptyState = document.getElementById('emptyState');
                if (emptyState) {
                    emptyState.classList.add('hidden');
                }
                
                ppmpItemCounter = 0;
                
                // Add items from database as cards
                data.items.forEach(item => {
                    ppmpItemCounter++;
                    
                    // Build LIB mapping HTML
                    let libMappingHTML = '';
                    if (item.lib_category && item.lib_particulars && item.lib_account_code) {
                        libMappingHTML = `
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-bold text-green-800">Linked to LIB</span>
                                        </div>
                                        <div class="text-sm font-semibold text-gray-800 mb-1">${item.lib_particulars}</div>
                                        <div class="text-xs text-gray-600">UACS Code: <span class="font-mono font-semibold">${item.lib_account_code}</span></div>
                                        <div class="text-xs text-gray-500 mt-1">Category: ${item.lib_category}</div>
                                    </div>
                                    <button type="button" onclick="clearLibMapping(${ppmpItemCounter})" 
                                        class="text-red-600 hover:bg-red-50 rounded-full p-2 transition-all ml-2" title="Clear Link">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        libMappingHTML = `
                            <button type="button" onclick="showLibExpenseSelector(${ppmpItemCounter})" 
                                class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 flex items-center justify-center gap-2 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                Link to LIB
                            </button>
                        `;
                    }
                    
                    const itemCard = document.createElement('div');
                    itemCard.id = `ppmpItem${ppmpItemCounter}`;
                    itemCard.className = 'bg-white rounded-lg shadow-md border-2 border-gray-200 hover:border-purple-400 transition-all p-6';
                    
                    // Check if unit or mode is custom
                    const standardUnits = ['box', 'boxes', 'bottle', 'bottles', 'meter', 'meters', 'pack', 'packs', 'pad', 'pads', 'pcs', 'ream', 'reams', 'roll', 'rolls', 'room', 'rooms', 'set', 'sets', 'unit', 'PAX'];
                    const isCustomUnit = !standardUnits.includes(item.unit);
                    const unitValue = isCustomUnit ? 'custom' : item.unit;
                    
                    const standardModes = ['Agency to Agency', 'Small Value Procurement'];
                    const isCustomMode = !standardModes.includes(item.recommended_mode);
                    const modeValue = isCustomMode ? 'custom' : item.recommended_mode;
                    
                    // Format dates - convert YYYY-MM-DD to YYYY-MM for month input, and handle 0000-00-00
                    const startDate = (item.start_procurement && item.start_procurement !== '0000-00-00') ? item.start_procurement.substring(0, 7) : '';
                    const endDate = (item.end_ads_posting && item.end_ads_posting !== '0000-00-00') ? item.end_ads_posting.substring(0, 7) : '';
                    const deliveryDate = (item.expected_delivery && item.expected_delivery !== '0000-00-00') ? item.expected_delivery.substring(0, 7) : '';
                    
                    // Check if type is custom
                    const standardTypes = ['Goods', 'Service', 'Infrastructure'];
                    const isCustomType = !standardTypes.includes(item.project_type);
                    const typeValue = isCustomType ? 'custom' : item.project_type;
                    
                    itemCard.innerHTML = `
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-purple-100 text-purple-700 rounded-full w-10 h-10 flex items-center justify-center font-bold">
                                    ${ppmpItemCounter}
                                </div>
                                <h5 class="text-lg font-bold text-gray-800">Item #${ppmpItemCounter}</h5>
                            </div>
                            <button type="button" onclick="removePPMPItem(${ppmpItemCounter})" 
                                class="text-red-600 hover:bg-red-50 rounded-full p-2 transition-all" title="Remove Item">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Description -->
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <svg class="w-4 h-4 inline-block mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                    </svg>
                                    General Description & Objective
                                </label>
                                <textarea name="general_description[]" rows="2" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none" 
                                    placeholder="Describe the item and its purpose...">${item.general_description || ''}</textarea>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                                <select name="project_type[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 type-select">
                                    <option value="">Select Type</option>
                                    <option value="Goods" ${typeValue === 'Goods' ? 'selected' : ''}>Goods</option>
                                    <option value="Service" ${typeValue === 'Service' ? 'selected' : ''}>Service</option>
                                    <option value="Infrastructure" ${typeValue === 'Infrastructure' ? 'selected' : ''}>Infrastructure</option>
                                    <option value="custom" ${isCustomType ? 'selected' : ''}>+ Custom</option>
                                </select>
                                <input type="text" name="type_custom[]" value="${isCustomType ? item.project_type : ''}" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mt-2 ${isCustomType ? '' : 'hidden'}" placeholder="Enter custom type">
                            </div>

                            <!-- Quantity -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                                <input type="number" step="1" name="quantity[]" value="${item.quantity || ''}" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                                    placeholder="0">
                            </div>

                            <!-- Unit -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit</label>
                                <select name="unit[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 unit-select">
                                    <option value="">Select Unit</option>
                                    <option value="box" ${unitValue === 'box' ? 'selected' : ''}>box</option>
                                    <option value="boxes" ${unitValue === 'boxes' ? 'selected' : ''}>boxes</option>
                                    <option value="bottle" ${unitValue === 'bottle' ? 'selected' : ''}>bottle</option>
                                    <option value="bottles" ${unitValue === 'bottles' ? 'selected' : ''}>bottles</option>
                                    <option value="meter" ${unitValue === 'meter' ? 'selected' : ''}>meter</option>
                                    <option value="meters" ${unitValue === 'meters' ? 'selected' : ''}>meters</option>
                                    <option value="pack" ${unitValue === 'pack' ? 'selected' : ''}>pack</option>
                                    <option value="packs" ${unitValue === 'packs' ? 'selected' : ''}>packs</option>
                                    <option value="pad" ${unitValue === 'pad' ? 'selected' : ''}>pad</option>
                                    <option value="pads" ${unitValue === 'pads' ? 'selected' : ''}>pads</option>
                                    <option value="pcs" ${unitValue === 'pcs' ? 'selected' : ''}>pcs</option>
                                    <option value="ream" ${unitValue === 'ream' ? 'selected' : ''}>ream</option>
                                    <option value="reams" ${unitValue === 'reams' ? 'selected' : ''}>reams</option>
                                    <option value="roll" ${unitValue === 'roll' ? 'selected' : ''}>roll</option>
                                    <option value="rolls" ${unitValue === 'rolls' ? 'selected' : ''}>rolls</option>
                                    <option value="room" ${unitValue === 'room' ? 'selected' : ''}>room</option>
                                    <option value="rooms" ${unitValue === 'rooms' ? 'selected' : ''}>rooms</option>
                                    <option value="set" ${unitValue === 'set' ? 'selected' : ''}>set</option>
                                    <option value="sets" ${unitValue === 'sets' ? 'selected' : ''}>sets</option>
                                    <option value="unit" ${unitValue === 'unit' ? 'selected' : ''}>unit</option>
                                    <option value="PAX" ${unitValue === 'PAX' ? 'selected' : ''}>PAX</option>
                                    <option value="custom" ${isCustomUnit ? 'selected' : ''}>+ Custom</option>
                                </select>
                                <input type="text" name="unit_custom[]" value="${isCustomUnit ? item.unit : ''}" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mt-2 ${isCustomUnit ? '' : 'hidden'}" placeholder="Enter custom unit">
                            </div>

                            <!-- Mode -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Recommended Mode of Procurement</label>
                                <select name="recommended_mode[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 mode-select">
                                    <option value="">Select Mode</option>
                                    <option value="Agency to Agency" ${modeValue === 'Agency to Agency' ? 'selected' : ''}>Agency to Agency</option>
                                    <option value="Small Value Procurement" ${modeValue === 'Small Value Procurement' ? 'selected' : ''}>Small Value Procurement</option>
                                    <option value="custom" ${isCustomMode ? 'selected' : ''}>+ Custom</option>
                                </select>
                                <input type="text" name="mode_custom[]" value="${isCustomMode ? item.recommended_mode : ''}" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mt-2 ${isCustomMode ? '' : 'hidden'}" placeholder="Enter custom mode">
                            </div>

                            <!-- Pre-Procurement -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pre-Procurement Conference</label>
                                <select name="pre_procurement[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="N" ${item.pre_procurement_conference === 'N' ? 'selected' : ''}>No</option>
                                    <option value="Y" ${item.pre_procurement_conference === 'Y' ? 'selected' : ''}>Yes</option>
                                </select>
                            </div>

                            <!-- Dates -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Start of Procurement</label>
                                <input type="month" name="start_procurement[]" value="${startDate}" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">End of Ads/Posting</label>
                                <input type="month" name="end_ads_posting[]" value="${endDate}" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Expected Delivery</label>
                                <input type="month" name="expected_delivery[]" value="${deliveryDate}" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Source of Funds -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Source of Funds</label>
                                <select name="source_of_funds[]" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select Source</option>
                                    <option value="IGF" ${item.source_of_funds === 'IGF' ? 'selected' : ''}>Internally Generated Funds (IGF)</option>
                                    <option value="RAF" ${item.source_of_funds === 'RAF' ? 'selected' : ''}>Regular Agency Fund (RAF)</option>
                                    <option value="BRF" ${item.source_of_funds === 'BRF' ? 'selected' : ''}>Business Related Fund (BRF)</option>
                                    <option value="TF" ${item.source_of_funds === 'TF' ? 'selected' : ''}>Trust Fund (TF)</option>
                                    <option value="TR" ${item.source_of_funds === 'TR' ? 'selected' : ''}>Trust Receipts (TR)</option>
                                </select>
                            </div>

                            <!-- Budget -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <svg class="w-4 h-4 inline-block mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Estimated Budget
                                </label>
                                <input type="number" step="0.01" name="estimated_budget[]" value="${item.estimated_budget || ''}" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                                    placeholder="0.00">
                            </div>

                            <!-- LIB Expense Link -->
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <svg class="w-4 h-4 inline-block mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    Link to LIB Expense Category
                                </label>
                                <div class="lib-mapping-cell">
                                    ${libMappingHTML}
                                </div>
                            </div>

                            <!-- Hidden fields for allocated and remarks -->
                            <input type="hidden" name="allocated_supporting[]" value="${item.allocated_supporting_funds || '0'}">
                            <input type="hidden" name="remarks[]" value="${item.deducted_from_categories || item.remarks || ''}">
                            
                            <!-- Hidden fields for LIB mapping -->
                            <input type="hidden" name="lib_category[]" value="${item.lib_category || ''}">
                            <input type="hidden" name="lib_particulars[]" value="${item.lib_particulars || ''}">
                            <input type="hidden" name="lib_account_code[]" value="${item.lib_account_code || ''}">
                        </div>
                    `;
                    
                    container.appendChild(itemCard);
                    
                    // Setup custom inputs for this card
                    setupCustomInputs(itemCard);
                });
                
                // Update item count and search visibility after loading all items
                updateItemCount();
                
                document.getElementById('ppmpModal').classList.remove('hidden');
            } else {
                console.error('API returned error:', data.message);
                alert('Error loading PPMP: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            console.error('Error stack:', error.stack);
            alert('An error occurred while loading the PPMP: ' + error.message);
        });
}

function deletePPMP(id) {
    if (!confirm('Are you sure you want to delete this PPMP? This action cannot be undone.')) {
        return;
    }
    
    fetch('../api/delete_ppmp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Force reload the current tab
            loadCurrentPPMP(currentTab, true);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the PPMP');
    });
}

// Tab switching function
function switchPPMPTab(tabName) {
    currentTab = tabName;
    
    // Update tab button styles
    document.querySelectorAll('.ppmp-tab-btn').forEach(btn => {
        btn.classList.remove('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
        btn.classList.add('border-transparent', 'text-gray-500', 'font-medium');
    });
    
    // Highlight selected tab
    const selectedTab = document.getElementById('ppmpTab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
        selectedTab.classList.add('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
    }
    
    // Show/hide content panels
    const ppmpContent = document.getElementById('ppmpTabContent');
    const supplementalContent = document.getElementById('supplementalTabContent');
    
    if (tabName === 'ppmp') {
        if (ppmpContent) ppmpContent.classList.remove('hidden');
        if (supplementalContent) supplementalContent.classList.add('hidden');
    } else if (tabName === 'supplemental') {
        if (ppmpContent) ppmpContent.classList.add('hidden');
        if (supplementalContent) supplementalContent.classList.remove('hidden');
    }
    
    // Apply current year filter when switching tabs
    const yearFilter = document.getElementById('yearFilter');
    const currentYear = yearFilter ? yearFilter.value : null;
    
    // Reload the tab content with current year filter
    loadCurrentPPMP(tabName, true, currentYear || null);
    
    // Save current tab to localStorage
    localStorage.setItem('activePPMPTab', tabName);
}

// Toggle create PPMP dropdown
function toggleCreatePPMPDropdown() {
    const dropdown = document.getElementById('createPPMPDropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('createPPMPDropdown');
    const button = document.getElementById('createPPMPButton');
    
    if (dropdown && button && !button.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Load current PPMP based on type (only if not already loaded)
function loadCurrentPPMP(ppmpType = 'ppmp', forceReload = false, filterYear = null) {
    // Get department ID from global variable set in HTML
    const departmentId = window.DEPARTMENT_ID || null;
    if (!departmentId) return;
    
    // Determine which container to use
    const containerId = ppmpType === 'supplemental' ? 'currentSupplementalContainer' : 'currentPPMPContainer';
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Check if content already exists (not the default empty state)
    const hasContent = container.querySelector('.screen-only-header') !== null;
    
    // If content exists and we're not forcing a reload, don't reload
    if (hasContent && !forceReload) {
        return;
    }
    
    // Build URL with filters
    let url = `../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=${ppmpType}`;
    if (filterYear) {
        url += `&fiscal_year=${filterYear}`;
    }
    
    // Load both approved and draft PPMPs
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ppmps && data.ppmps.length > 0) {
                // Find the most recent PPMP (approved first, then draft)
                let latestPPMP = data.ppmps.find(p => p.status === 'approved');
                if (!latestPPMP) {
                    latestPPMP = data.ppmps.find(p => p.status === 'draft');
                }
                
                if (latestPPMP) {
                    displayCurrentPPMP(latestPPMP.id, ppmpType);
                }
                
                // Show supplemental tab if user has created supplemental
                if (ppmpType === 'supplemental' || data.ppmps.some(p => p.ppmp_type === 'supplemental')) {
                    const suppTab = document.getElementById('ppmpTab-supplemental');
                    if (suppTab) {
                        suppTab.classList.remove('hidden');
                    }
                }
            } else {
                // No PPMP found for this type
                const typeName = ppmpType === 'supplemental' ? 'Supplemental PPMP' : 'PPMP';
                const yearText = filterYear ? ` for ${filterYear}` : '';
                container.innerHTML = `
                    <div class="text-center py-12 text-gray-500">
                        <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg font-semibold mb-2">No ${typeName} Found${yearText}</p>
                        <p class="text-sm">Click "Create New PPMP" to get started</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading PPMP:', error);
        });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Get the default year from the filter (2026)
    const yearFilter = document.getElementById('yearFilter');
    const defaultYear = yearFilter ? yearFilter.value : '2026';
    
    // Load PPMP content initially with year filter
    loadCurrentPPMP('ppmp', true, defaultYear);
    
    // Check if user has any supplemental PPMPs to show the tab
    const departmentId = window.DEPARTMENT_ID || null;
    if (departmentId) {
        let url = `../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=supplemental`;
        if (defaultYear) {
            url += `&fiscal_year=${defaultYear}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.ppmps && data.ppmps.length > 0) {
                    const suppTab = document.getElementById('ppmpTab-supplemental');
                    if (suppTab) {
                        suppTab.classList.remove('hidden');
                    }
                    // Load supplemental content as well with year filter
                    loadCurrentPPMP('supplemental', true, defaultYear);
                }
            });
    }
    
    // Restore active tab from localStorage (just switch UI, don't reload)
    const savedTab = localStorage.getItem('activePPMPTab');
    if (savedTab && (savedTab === 'ppmp' || savedTab === 'supplemental')) {
        switchPPMPTab(savedTab);
    }
});


// ============================================================================
// NEW PPMP-LIB INTEGRATION FEATURES
// ============================================================================

// LIB Expense Categories Cache
let libExpenseCategories = null;
let ppmpItems = []; // Store PPMP items with LIB mappings

// Load LIB expense categories
function loadLibExpenseCategories() {
    if (libExpenseCategories) {
        return Promise.resolve(libExpenseCategories);
    }
    
    return fetch(`../api/get_lib_expense_categories.php?department_id=${window.DEPARTMENT_ID}&fiscal_year=${new Date().getFullYear()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                libExpenseCategories = data.categories;
                return libExpenseCategories;
            }
            return {};
        })
        .catch(error => {
            console.error('Error loading LIB categories:', error);
            return {};
        });
}

// Show LIB expense selector modal
function showLibExpenseSelector(itemIndex) {
    const modal = document.getElementById('libExpenseSelectorModal');
    const card = document.getElementById(`ppmpItem${itemIndex}`);
    
    if (!card) return;
    
    // Get item data from form
    const description = card.querySelector('textarea[name="general_description[]"]').value;
    const budget = card.querySelector('input[name="estimated_budget[]"]').value;
    
    document.getElementById('libSelectorItemIndex').value = itemIndex;
    document.getElementById('libSelectorItemName').textContent = description || `Item #${itemIndex}`;
    document.getElementById('libSelectorItemBudget').textContent = `â‚±${parseFloat(budget || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    
    // Load categories
    loadLibExpenseCategories().then(categories => {
        renderLibExpenseCategories(categories);
    });
    
    modal.classList.remove('hidden');
}

// Render LIB expense categories in selector
function renderLibExpenseCategories(categories) {
    const container = document.getElementById('libExpenseCategoriesContainer');
    container.innerHTML = '';
    
    const categoryColors = {
        'A. PERSONAL SERVICES': 'blue',
        'B. Maintenance & Other Operating Expenses': 'green',
        'C. Capital Outlay': 'purple'
    };
    
    for (const [categoryName, expenses] of Object.entries(categories)) {
        const color = categoryColors[categoryName] || 'gray';
        
        const categoryDiv = document.createElement('div');
        categoryDiv.className = 'mb-6';
        categoryDiv.innerHTML = `
            <h4 class="text-lg font-bold text-${color}-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                ${categoryName}
            </h4>
            <div class="grid grid-cols-1 gap-2" id="category_${categoryName.replace(/[^a-zA-Z]/g, '')}"></div>
        `;
        
        container.appendChild(categoryDiv);
        
        const expensesContainer = categoryDiv.querySelector(`#category_${categoryName.replace(/[^a-zA-Z]/g, '')}`);
        
        expenses.forEach(expense => {
            const expenseBtn = document.createElement('button');
            expenseBtn.type = 'button';
            expenseBtn.className = `text-left px-4 py-3 bg-${color}-50 hover:bg-${color}-100 border-2 border-${color}-200 rounded-lg transition-all`;
            expenseBtn.innerHTML = `
                <div class="font-semibold text-${color}-900 text-sm">${expense.name}</div>
                <div class="text-xs text-${color}-700 mt-1">Code: ${expense.code}</div>
            `;
            expenseBtn.onclick = () => selectLibExpense(categoryName, expense.name, expense.code);
            
            expensesContainer.appendChild(expenseBtn);
        });
    }
}

// Select LIB expense for PPMP item
function selectLibExpense(category, particulars, accountCode) {
    const itemIndex = parseInt(document.getElementById('libSelectorItemIndex').value);
    const card = document.getElementById(`ppmpItem${itemIndex}`);
    
    if (card) {
        // Store LIB mapping in hidden inputs
        let libCategoryInput = card.querySelector('input[name="lib_category[]"]');
        let libParticularsInput = card.querySelector('input[name="lib_particulars[]"]');
        let libAccountCodeInput = card.querySelector('input[name="lib_account_code[]"]');
        
        if (!libCategoryInput) {
            libCategoryInput = document.createElement('input');
            libCategoryInput.type = 'hidden';
            libCategoryInput.name = 'lib_category[]';
            card.appendChild(libCategoryInput);
        }
        
        if (!libParticularsInput) {
            libParticularsInput = document.createElement('input');
            libParticularsInput.type = 'hidden';
            libParticularsInput.name = 'lib_particulars[]';
            card.appendChild(libParticularsInput);
        }
        
        if (!libAccountCodeInput) {
            libAccountCodeInput = document.createElement('input');
            libAccountCodeInput.type = 'hidden';
            libAccountCodeInput.name = 'lib_account_code[]';
            card.appendChild(libAccountCodeInput);
        }
        
        libCategoryInput.value = category;
        libParticularsInput.value = particulars;
        libAccountCodeInput.value = accountCode;
        
        // Update UI to show selection
        const libCell = card.querySelector('.lib-mapping-cell');
        if (libCell) {
            libCell.innerHTML = `
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-bold text-green-800">Linked to LIB</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-800 mb-1">${particulars}</div>
                            <div class="text-xs text-gray-600">UACS Code: <span class="font-mono font-semibold">${accountCode}</span></div>
                            <div class="text-xs text-gray-500 mt-1">Category: ${category}</div>
                        </div>
                        <button type="button" onclick="clearLibMapping(${itemIndex})" 
                            class="text-red-600 hover:bg-red-50 rounded-full p-2 transition-all ml-2" title="Clear Link">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }
        
        closeLibExpenseSelector();
        
        // Show success message
        showToast('LIB expense category linked successfully', 'success');
    }
}

// Clear LIB mapping for an item
function clearLibMapping(itemIndex) {
    const card = document.getElementById(`ppmpItem${itemIndex}`);
    if (card) {
        const libCategoryInput = card.querySelector('input[name="lib_category[]"]');
        const libParticularsInput = card.querySelector('input[name="lib_particulars[]"]');
        const libAccountCodeInput = card.querySelector('input[name="lib_account_code[]"]');
        
        if (libCategoryInput) libCategoryInput.value = '';
        if (libParticularsInput) libParticularsInput.value = '';
        if (libAccountCodeInput) libAccountCodeInput.value = '';
        
        const libCell = card.querySelector('.lib-mapping-cell');
        if (libCell) {
            libCell.innerHTML = `
                <button type="button" onclick="showLibExpenseSelector(${itemIndex})" 
                    class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 flex items-center justify-center gap-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    Link to LIB
                </button>
            `;
        }
        
        showToast('LIB mapping cleared', 'info');
    }
}

// Close LIB expense selector
function closeLibExpenseSelector() {
    document.getElementById('libExpenseSelectorModal').classList.add('hidden');
}

// Search LIB expenses
function searchLibExpenses() {
    const searchTerm = document.getElementById('libExpenseSearch').value.toLowerCase();
    const allExpenses = document.querySelectorAll('#libExpenseCategoriesContainer button');
    
    allExpenses.forEach(btn => {
        const text = btn.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            btn.style.display = 'block';
        } else {
            btn.style.display = 'none';
        }
    });
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-[100] px-6 py-4 rounded-lg shadow-lg animate-fade-in ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        'bg-blue-600'
    } text-white font-semibold`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Sync PPMP to LIB when finalized
function syncPPMPToLIB(ppmpId) {
    return fetch('../api/sync_ppmp_to_lib.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ppmp_id: ppmpId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Successfully synced ${data.synced_count} items to LIB`, 'success');
            return true;
        } else {
            showToast('Error syncing to LIB: ' + data.message, 'error');
            return false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error syncing to LIB', 'error');
        return false;
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('createPPMPDropdown');
    const button = event.target.closest('button[onclick="toggleCreatePPMPDropdown()"]');
    if (dropdown && !button && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Toggle PPMP item details
function togglePPMPDetails(itemIndex) {
    const detailsRow = document.getElementById(`details-${itemIndex}`);
    const arrow = document.getElementById(`arrow-${itemIndex}`);
    
    if (detailsRow && arrow) {
        const isHidden = detailsRow.classList.contains('hidden');
        
        if (isHidden) {
            // Expand
            detailsRow.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)';
        } else {
            // Collapse
            detailsRow.classList.add('hidden');
            arrow.style.transform = 'rotate(0deg)';
        }
    }
}

// Expand all PPMP details
function expandAllPPMPDetails() {
    const allDetailsRows = document.querySelectorAll('[id^="details-"]');
    const allArrows = document.querySelectorAll('[id^="arrow-"]');
    
    allDetailsRows.forEach(row => row.classList.remove('hidden'));
    allArrows.forEach(arrow => arrow.style.transform = 'rotate(180deg)');
}

// Collapse all PPMP details
function collapseAllPPMPDetails() {
    const allDetailsRows = document.querySelectorAll('[id^="details-"]');
    const allArrows = document.querySelectorAll('[id^="arrow-"]');
    
    allDetailsRows.forEach(row => row.classList.add('hidden'));
    allArrows.forEach(arrow => arrow.style.transform = 'rotate(0deg)');
}
