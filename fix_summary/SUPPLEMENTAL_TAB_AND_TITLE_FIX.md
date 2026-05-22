# Supplemental Tab and Title Fix

## Issues Fixed

### 1. ✅ Supplemental Tab Appears After Creating Supplemental
**Problem**: Supplemental tab wasn't appearing automatically after creating a supplemental PPMP (draft or final).

**Solution**: Updated `savePPMP()` function in `assets/js/ppmp.js`:
- Check if `ppmp_type === 'supplemental'` in the response
- Show the supplemental tab by removing `hidden` class
- Automatically switch to supplemental tab to display the newly created supplemental

**Code Added**:
```javascript
// If supplemental was created, show the supplemental tab
if (data.ppmp_type === 'supplemental') {
    const suppTab = document.getElementById('ppmpTab-supplemental');
    if (suppTab) {
        suppTab.classList.remove('hidden');
    }
    // Switch to supplemental tab to show the newly created supplemental
    switchPPMPTab('supplemental');
}
```

### 2. ✅ Title Changes Based on PPMP Type
**Problem**: Both regular PPMP and Supplemental showed "Project Procurement Management Plan (PPMP)" as the title.

**Solution**: Updated `generatePPMPView()` function to show different titles:
- **Regular PPMP**: "Project Procurement Management Plan (PPMP)"
- **Supplemental**: "SUPPLEMENTAL (PPMP)"

**Implementation**:
```javascript
// Determine title based on ppmp_type
const isSupplemental = ppmp.ppmp_type === 'supplemental';
const screenTitle = isSupplemental ? 'SUPPLEMENTAL (PPMP)' : 'Project Procurement Management Plan (PPMP)';
const printTitle = isSupplemental ? 'SUPPLEMENTAL (PPMP)' : 'PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP)';
```

## User Experience Flow

### Creating Supplemental PPMP:
1. User clicks "Create New PPMP" dropdown
2. Selects "Supplemental PPMP"
3. Fills in the form
4. Saves as Draft or Final
5. **Supplemental tab appears automatically** ✨
6. **Page switches to Supplemental tab** showing the new supplemental
7. **Title shows "SUPPLEMENTAL (PPMP)"** instead of regular PPMP title

### Viewing Supplemental:
- Screen view header: "SUPPLEMENTAL (PPMP)"
- Print header: "SUPPLEMENTAL (PPMP)"
- Tab badge: Blue "Supplemental" badge in drafts

## Files Modified
1. `assets/js/ppmp.js`:
   - Updated `savePPMP()` function
   - Updated `generatePPMPView()` function

## Testing Checklist
- [ ] Create a supplemental PPMP (draft)
- [ ] Verify supplemental tab appears
- [ ] Verify page switches to supplemental tab
- [ ] Verify title shows "SUPPLEMENTAL (PPMP)"
- [ ] Create a supplemental PPMP (final)
- [ ] Verify same behavior for final
- [ ] Switch between PPMP and Supplemental tabs
- [ ] Verify titles change correctly
- [ ] Print supplemental and verify title in print view

## Visual Comparison

### Regular PPMP:
```
┌─────────────────────────────────────────────┐
│ 📄 Project Procurement Management Plan     │
│                                (PPMP)       │
└─────────────────────────────────────────────┘
```

### Supplemental PPMP:
```
┌─────────────────────────────────────────────┐
│ 📄 SUPPLEMENTAL (PPMP)                      │
│                                             │
└─────────────────────────────────────────────┘
```

## Notes
- Tab appears immediately after save (no page reload needed)
- Automatic tab switching provides better UX
- Title distinction makes it clear which type is being viewed
- Works for both draft and final supplemental PPMPs
