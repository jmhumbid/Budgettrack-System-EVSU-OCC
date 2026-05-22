# PPMP Item Search Feature

## Overview

Added a search bar to the PPMP creation/edit modal that helps users quickly find and navigate to specific items when working with large procurement plans (5+ items).

## Features

### 1. **Smart Visibility**
- Search bar automatically appears when there are **5 or more items**
- Hidden when there are fewer than 5 items to keep the interface clean
- Item count badge shows total number of items

### 2. **Comprehensive Search**
The search functionality searches across multiple fields:
- General Description & Objective
- Type (Goods, Service, Infrastructure, or custom)
- Unit (box, pcs, ream, etc., or custom)
- Recommended Mode of Procurement
- Estimated Budget
- Source of Funds

### 3. **Visual Feedback**
- **Matching items**: Highlighted with a purple ring border
- **Non-matching items**: Hidden from view
- **Search results**: Shows count of matching items
- **No results**: Displays "No items found" message
- **Auto-scroll**: Automatically scrolls to the first matching item

### 4. **User-Friendly Controls**
- **Clear button**: Appears when search has text, click to clear search
- **Real-time search**: Results update as you type
- **Smooth animations**: Items fade in/out smoothly

## How to Use

### For Users:

1. **Open PPMP Creation/Edit Modal**
   - Click "Create New PPMP" or edit an existing PPMP

2. **Add Items**
   - Add at least 5 items to see the search bar appear
   - The search bar shows automatically in the "Procurement Items" section

3. **Search for Items**
   - Type in the search box to find items by:
     - Description (e.g., "office supplies")
     - Type (e.g., "goods")
     - Budget amount (e.g., "1000")
     - Any other field content

4. **View Results**
   - Matching items are highlighted with a purple border
   - Non-matching items are hidden
   - The page automatically scrolls to the first match
   - See the count of matching items below the search box

5. **Clear Search**
   - Click the X button in the search box
   - Or delete all text to show all items again

## Technical Implementation

### Files Modified:

1. **`pages/ppmp.php`**
   - Added search bar HTML in the "Procurement Items" section
   - Added item count badge
   - Search container with input field and clear button
   - Results info display

2. **`assets/js/ppmp.js`**
   - Added `updateItemCount()` function
   - Added `searchPPMPItems()` function
   - Added `clearItemSearch()` function
   - Updated `addPPMPItem()` to call `updateItemCount()`
   - Updated `removePPMPItem()` to call `updateItemCount()`
   - Updated `editPPMP()` to call `updateItemCount()` after loading items

### Key Functions:

#### `updateItemCount()`
- Counts total items in the container
- Updates the item count badge
- Shows/hides search bar based on item count (≥5 items)
- Called after adding, removing, or loading items

#### `searchPPMPItems()`
- Gets search term from input
- Searches across all item fields
- Highlights matching items with purple ring
- Hides non-matching items
- Shows search results count
- Auto-scrolls to first match
- Shows/hides clear button

#### `clearItemSearch()`
- Clears search input
- Shows all items
- Removes highlighting
- Hides results info and clear button

## UI/UX Design

### Search Bar Styling:
- **Border**: Purple (matches PPMP theme)
- **Icon**: Magnifying glass on the left
- **Clear button**: X icon on the right (when active)
- **Placeholder**: "Search items by description, type, or budget..."

### Item Highlighting:
- **Matching items**: 4px purple ring with 50% opacity
- **Smooth transitions**: Items fade in/out smoothly
- **Scroll behavior**: Smooth scroll to first match

### Results Display:
- **Success**: Purple text showing match count
- **No results**: Red text showing "No items found"
- **Position**: Below search bar

## Benefits

1. **Efficiency**: Quickly find specific items in large PPMPs (50+ items)
2. **User-Friendly**: Intuitive search with real-time results
3. **Smart UI**: Only shows when needed (5+ items)
4. **Comprehensive**: Searches across all relevant fields
5. **Visual Clarity**: Clear highlighting and feedback

## Example Use Cases

### Scenario 1: Large PPMP with 50+ Items
- User creates a PPMP with 50 office supply items
- Search bar appears automatically
- User types "paper" to find all paper-related items
- System shows 8 matching items, hides the rest
- User can quickly edit all paper items

### Scenario 2: Budget Review
- User needs to find all items with budget > ₱10,000
- Types "10000" in search
- System shows all items with that budget amount
- User can review and adjust as needed

### Scenario 3: Type-Based Search
- User wants to find all "Service" type items
- Types "service" in search
- System shows only service items
- User can verify all services are properly categorized

## Future Enhancements (Optional)

1. **Advanced Filters**: Add dropdown filters for Type, Source of Funds, etc.
2. **Sort Options**: Sort by budget, date, type, etc.
3. **Bulk Actions**: Select multiple items from search results for bulk edit
4. **Search History**: Remember recent searches
5. **Keyboard Shortcuts**: Ctrl+F to focus search, Esc to clear

## Status

✅ **IMPLEMENTED** - Search feature is fully functional and ready to use!

## Testing Checklist

- [x] Search bar appears when 5+ items are added
- [x] Search bar hides when fewer than 5 items
- [x] Search works across all fields (description, type, unit, budget, etc.)
- [x] Matching items are highlighted with purple ring
- [x] Non-matching items are hidden
- [x] Results count is displayed correctly
- [x] Clear button appears/disappears appropriately
- [x] Auto-scroll to first match works
- [x] Item count badge updates correctly
- [x] Search persists when editing existing PPMP
- [x] Search clears when modal is closed
