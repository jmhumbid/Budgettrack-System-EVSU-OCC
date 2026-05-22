# LIB Generate Button and PPMP Label Update

## Overview
Updated the LIB page to simplify the button text and properly distinguish PPMP items from Allocation items.

## Changes Made

### 1. Simplified "Generate" Button Text
**File**: `pages/lib.php`

**Before**:
```html
Auto-Generate from Allocations
```

**After**:
```html
Generate
```

**Also Updated**:
- Empty state message changed from "Use 'Auto-Generate from Allocations' to create a LIB..." to "Use 'Generate' to create a LIB..."

### 2. Added PPMP Source Label
**File**: `pages/lib.php`

**Location**: Auto-generate LIB modal - item display logic

**Before**:
```javascript
const sourceLabel = item.is_custom ? 
    '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Custom</span>' :
    '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Allocation</span>';
```

**After**:
```javascript
const sourceLabel = item.is_custom ? 
    '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Custom</span>' :
    (item.source === 'ppmp' ? 
        '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">PPMP</span>' :
        '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Allocation</span>');
```

### 3. Updated Action Column Text for PPMP Items
**File**: `pages/lib.php`

**Location**: Auto-generate LIB modal - action column

**Before**:
```javascript
${item.is_custom ? `[edit/delete buttons]` : '<span class="text-gray-400 text-xs">From Allocation</span>'}
```

**After**:
```javascript
${item.is_custom ? `[edit/delete buttons]` : 
    (item.source === 'ppmp' ? 
        '<span class="text-gray-400 text-xs">From PPMP</span>' : 
        '<span class="text-gray-400 text-xs">From Allocation</span>')}
```

## Visual Changes

### Source Labels (Badge Colors)
- **Custom Items**: Blue badge with "Custom" text
- **PPMP Items**: Purple badge with "PPMP" text (NEW)
- **Allocation Items**: Green badge with "Allocation" text

### Action Column Text
- **Custom Items**: Edit and Delete buttons (editable)
- **PPMP Items**: "From PPMP" text (read-only)
- **Allocation Items**: "From Allocation" text (read-only)

## How It Works

The system now properly distinguishes three types of LIB items:

1. **Custom Items** (`is_custom: true`)
   - Manually added by users
   - Can be edited and deleted
   - Blue badge

2. **PPMP Items** (`source: 'ppmp'`)
   - Auto-generated from PPMP data
   - Read-only (cannot edit/delete)
   - Purple badge
   - Shows "From PPMP" in action column

3. **Allocation Items** (`source: 'allocation'`)
   - Auto-generated from allocation data
   - Read-only (cannot edit/delete)
   - Green badge
   - Shows "From Allocation" in action column

## Data Source

The `source` field is set in `api/generate_auto_lib.php`:
- PPMP items: `'source' => 'ppmp'` (line 203)
- Allocation items: `'source' => 'allocation'` (lines 95, 127)
- Custom items: `'source' => 'custom'` (line 161)

## User Experience

Users can now:
- Quickly identify which items came from PPMP vs Allocations
- See a cleaner, simpler "Generate" button
- Understand that PPMP items are linked to their PPMP entries
- Distinguish between three types of items at a glance

## Files Modified

- `pages/lib.php` - Button text, source labels, and action column logic

## Testing Checklist

- [ ] Verify "Generate" button displays correctly
- [ ] Generate a LIB with PPMP items
- [ ] Verify PPMP items show purple "PPMP" badge
- [ ] Verify PPMP items show "From PPMP" in action column
- [ ] Verify Allocation items show green "Allocation" badge
- [ ] Verify Allocation items show "From Allocation" in action column
- [ ] Verify Custom items show blue "Custom" badge
- [ ] Verify Custom items have edit/delete buttons
- [ ] Verify empty state message shows "Generate" text

## Related Documentation

- `LIB_CREATE_BUTTON_REMOVAL.md` - Previous button removal
- `LIB_PPMP_READONLY_FEATURE.md` - PPMP read-only implementation
- `api/generate_auto_lib.php` - Source field assignment
