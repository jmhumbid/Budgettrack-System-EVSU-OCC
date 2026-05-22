// Script to format UACS codes with pattern: 5 02 02 010 00
// Run this to generate the formatted codes

function formatUACSCode(code) {
    // Remove all spaces first
    const clean = code.replace(/\s/g, '');
    
    // Format as: 5 02 02 010 00 (1 digit, 2 digits, 2 digits, 3 digits, 2 digits)
    if (clean.length === 10) {
        return `${clean[0]} ${clean.substring(1,3)} ${clean.substring(3,5)} ${clean.substring(5,8)} ${clean.substring(8,10)}`;
    }
    return code; // Return as-is if not 10 digits
}

// Test
console.log(formatUACSCode("5020201002")); // Should output: 5 02 02 010 02
console.log(formatUACSCode("5020301002")); // Should output: 5 02 03 010 02
console.log(formatUACSCode("50203010 00")); // Should output: 5 02 03 010 00
