// UACS (Unified Accounts Code Structure) Codes Database
const UACS_CODES = {
    // Personnel Services (5010000000)
    "personnel_services": [
        { code: "5 01 01 010 01", name: "Basic Salary - Civilian", keywords: ["basic", "salary", "civilian"] },
        { code: "5010101002", name: "Base Pay - Military/Uniformed Personnel", keywords: ["base", "pay", "military", "uniformed"] },
        { code: "5010102000", name: "Salaries and Wages - Casual/Contractual", keywords: ["casual", "contractual", "wages"] },
        { code: "5010103000", name: "Salaries and Wages - Substitute Teachers", keywords: ["substitute", "teachers"] },
        { code: "5010201001", name: "PERA - Civilian", keywords: ["pera", "civilian", "economic", "relief"] },
        { code: "5010201002", name: "PERA - Military/Uniformed Personnel", keywords: ["pera", "military", "uniformed"] },
        { code: "5010202000", name: "Representation Allowance (RA)", keywords: ["representation", "allowance", "ra"] },
        { code: "5010203001", name: "Transportation Allowance (TA)", keywords: ["transportation", "allowance", "ta"] },
        { code: "5010203002", name: "RATA of Sectoral/Alternate Sectoral Representatives", keywords: ["rata", "sectoral", "alternate"] },
        { code: "5010204001", name: "Clothing/Uniform Allowance - Civilian", keywords: ["clothing", "uniform", "allowance", "civilian"] },
        { code: "5010204002", name: "Shoe Allowance - Civilian", keywords: ["shoe", "allowance", "civilian"] },
        { code: "5010204003", name: "Clothing/Uniform Allowance - Military/Uniformed Personnel", keywords: ["clothing", "uniform", "military"] },
        { code: "5010205001", name: "Subsistence Allowance - Military/Uniformed Personnel", keywords: ["subsistence", "allowance", "military"] },
        { code: "5010205002", name: "Subsistence Allowance - Magna Carta Benefits for Science and Technology under R.A. 8439", keywords: ["subsistence", "science", "technology", "8439"] },
        { code: "5010205003", name: "Subsistence Allowance - Magna Carta Benefits for Public Health Workers under R.A. 7305", keywords: ["subsistence", "health", "workers", "7305"] },
        { code: "5010205004", name: "Subsistence Allowance - Magna Carta Benefits for Public Social Workers under R.A. 9432", keywords: ["subsistence", "social", "workers", "9432"] },
        { code: "5010206001", name: "Laundry Allowance - Civilian", keywords: ["laundry", "allowance", "civilian"] },
        { code: "5010206002", name: "Laundry Allowance - Military/Uniformed Personnel", keywords: ["laundry", "military"] },
        { code: "5010207001", name: "Quarters Allowance - Civilian", keywords: ["quarters", "allowance", "civilian"] },
        { code: "5010207002", name: "Quarters Allowance - Military/Uniformed Personnel", keywords: ["quarters", "military"] },
        { code: "5010208001", name: "Productivity Incentive Allowance - Civilian", keywords: ["productivity", "incentive", "civilian"] },
        { code: "5010208002", name: "Productivity Incentive Allowance - Military/Uniformed Personnel", keywords: ["productivity", "military"] },
        { code: "5010209001", name: "Overseas Allowance - Civilian", keywords: ["overseas", "allowance", "civilian"] },
        { code: "5010209002", name: "Overseas Allowance - Military/Uniformed Personnel", keywords: ["overseas", "military"] },
        { code: "5010210001", name: "Honoraria - Civilian", keywords: ["honoraria", "civilian", "honor"] },
        { code: "5010210001", name: "Honoraria - Overload", keywords: ["honoraria", "overload", "honor"] },
        { code: "5010210001", name: "Honoraria - Part-time", keywords: ["honoraria", "part", "time", "parttime", "honor"] },
        { code: "5010210002", name: "Honoraria - Military/Uniformed Personnel", keywords: ["honoraria", "military"] },
        { code: "5010210003", name: "Honoraria - Magna Carta Benefits for Science and Technology under R.A. 8439", keywords: ["honoraria", "science", "technology"] },
        { code: "5010210004", name: "Honoraria - Magna Carta Benefits for Public Health Workers under R.A. 7305", keywords: ["honoraria", "health", "workers"] },
        { code: "5010210005", name: "Honoraria - Magna Carta Benefits for Public Social Workers under R.A. 9432", keywords: ["honoraria", "social", "workers"] },
        { code: "5010211001", name: "Hazard Pay", keywords: ["hazard", "pay"] },
        { code: "5010211002", name: "Hazard Duty Pay - Civilian", keywords: ["hazard", "duty", "civilian"] },
        { code: "5010211003", name: "Hazard Duty Pay - Military/Uniformed Personnel", keywords: ["hazard", "duty", "military"] },
        { code: "5010211007", name: "Radiation Hazard Pay not exceeding 15% of Basic Salary", keywords: ["radiation", "hazard"] },
        { code: "5010211008", name: "High Risk Duty Pay", keywords: ["high", "risk", "duty"] },
        { code: "5010211009", name: "Hazardous Duty Pay", keywords: ["hazardous", "duty"] },
        { code: "5010212001", name: "Longevity Pay - Civilian", keywords: ["longevity", "pay", "civilian"] },
        { code: "5010212002", name: "Longevity Pay - Military/Uniformed Personnel", keywords: ["longevity", "military"] },
        { code: "5010213001", name: "Overtime Pay", keywords: ["overtime", "pay"] },
        { code: "5010213002", name: "Night-shift Differential Pay", keywords: ["night", "shift", "differential"] },
        { code: "5010214001", name: "Year-End Bonus - Civilian", keywords: ["year", "end", "bonus", "civilian"] },
        { code: "5010214002", name: "Year-End Bonus - Military/Uniformed Personnel", keywords: ["year", "end", "bonus", "military"] },
        { code: "5010215001", name: "Cash Gift - Civilian", keywords: ["cash", "gift", "civilian"] },
        { code: "5010215002", name: "Cash Gift - Military/Uniformed Personnel", keywords: ["cash", "gift", "military"] },
        { code: "5010216001", name: "Mid-Year Bonus - Civilian", keywords: ["mid", "year", "bonus", "civilian"] },
        { code: "5010216002", name: "Mid-Year Bonus - Military/Uniformed Personnel", keywords: ["mid", "year", "bonus", "military"] },
        { code: "5010218000", name: "Medical Allowance", keywords: ["medical", "allowance"] },
        { code: "5010299001", name: "Per Diems - Civilian", keywords: ["per", "diem", "civilian"] },
        { code: "5010299012", name: "Productivity Enhancement Incentive - Civilian", keywords: ["productivity", "enhancement", "incentive", "civilian"] },
        { code: "5010299013", name: "Productivity Enhancement Incentive - Military/Uniformed Personnel", keywords: ["productivity", "enhancement", "military"] },
        { code: "5010299014", name: "Performance Based Bonus - Civilian", keywords: ["performance", "based", "bonus", "civilian"] },
        { code: "5010299015", name: "Performance Based Bonus - Military/Uniformed Personnel", keywords: ["performance", "based", "military"] },
        { code: "5010302001", name: "Pag-IBIG - Civilian", keywords: ["pag", "ibig", "civilian"] },
        { code: "5010302002", name: "Pag-IBIG - Military/Uniformed Personnel", keywords: ["pag", "ibig", "military"] },
        { code: "5010303001", name: "PhilHealth - Civilian", keywords: ["philhealth", "civilian"] },
        { code: "5010303002", name: "PhilHealth - Military/Uniformed Personnel", keywords: ["philhealth", "military"] },
        { code: "5010304001", name: "ECIP - Civilian", keywords: ["ecip", "civilian", "employees", "compensation", "insurance"] },
        { code: "5010304002", name: "ECIP - Military/Uniformed Personnel", keywords: ["ecip", "military", "employees", "compensation"] },
        { code: "5010401001", name: "Pension Benefits - Civilian", keywords: ["pension", "benefits", "civilian"] },
        { code: "5010402001", name: "Retirement Gratuity - Civilian", keywords: ["retirement", "gratuity", "civilian"] },
        { code: "5010403001", name: "Terminal Leave Benefits - Civilian", keywords: ["terminal", "leave", "benefits", "civilian"] }
    ],
    
    // Maintenance and Other Operating Expenses (5020000000)
    "mooe": [
        { code: "5020101000", name: "Traveling Expenses - Local", keywords: ["traveling", "travel", "local", "trip"] },
        { code: "5020102000", name: "Traveling Expenses - Foreign", keywords: ["traveling", "travel", "foreign", "international"] },
        { code: "5020201000", name: "Training Expenses", keywords: ["training", "seminar", "workshop"] },
        { code: "5020202000", name: "Scholarship Grants/Expenses", keywords: ["scholarship", "grants", "education"] },
        { code: "5020201000", name: "Office Supplies Expenses", keywords: ["office", "supplies", "paper", "pen", "stationery"] },
        { code: "5020302000", name: "Accountable Forms Expenses", keywords: ["accountable", "forms"] },
        { code: "5020303000", name: "Non-Accountable Forms Expenses", keywords: ["non", "accountable", "forms"] },
        { code: "5020305000", name: "Food Supplies Expenses", keywords: ["food", "supplies", "catering", "meals"] },
        { code: "5020307000", name: "Drugs and Medicines Expenses", keywords: ["drugs", "medicines", "pharmaceutical", "medical"] },
        { code: "5020308000", name: "Medical, Dental and Laboratory Supplies Expenses", keywords: ["medical", "dental", "laboratory", "supplies"] },
        { code: "5020309000", name: "Fuel, Oil and Lubricants Expenses", keywords: ["fuel", "oil", "lubricants", "gasoline", "diesel"] },
        { code: "5020311001", name: "Textbooks and Instructional Materials Expenses", keywords: ["textbooks", "instructional", "materials", "books"] },
        { code: "5020311002", name: "Chalk Allowance", keywords: ["chalk", "allowance"] },
        { code: "5020321002", name: "Semi-Expendable Office Equipment", keywords: ["semi", "expendable", "office", "equipment"] },
        { code: "5020321003", name: "Semi-Expendable ICT Equipment", keywords: ["semi", "expendable", "ict", "equipment", "computer"] },
        { code: "5020321010", name: "Semi-Expendable Medical Equipment", keywords: ["semi", "expendable", "medical", "equipment"] },
        { code: "5020321012", name: "Semi-Expendable Sports Equipment", keywords: ["semi", "expendable", "sports", "equipment"] },
        { code: "5020322001", name: "Semi-Expendable Furniture and Fixtures", keywords: ["semi", "expendable", "furniture", "fixtures"] },
        { code: "5020322002", name: "Semi-Expendable Books", keywords: ["semi", "expendable", "books"] },
        { code: "5020329000", name: "Prisoner Medical Support Expenses", keywords: ["prisoner", "medical", "support", "expenses"] },
        { code: "5020399000", name: "Other Supplies and Materials Expenses", keywords: ["other", "supplies", "materials", "expenses"] },
        { code: "5020401000", name: "Water Expenses", keywords: ["water", "expenses", "utility"] },
        { code: "5020402000", name: "Electricity Expenses", keywords: ["electricity", "expenses", "power", "utility"] },
        { code: "5020502001", name: "Telephone Expenses - Mobile", keywords: ["telephone", "mobile", "cellphone", "phone"] },
        { code: "5020502002", name: "Telephone Expenses - Landline", keywords: ["telephone", "landline", "phone"] },
        { code: "5020503000", name: "Internet Subscription Expenses", keywords: ["internet", "subscription", "wifi", "broadband"] },
        { code: "5020504000", name: "Cable, Satellite, Telegraph and Radio Expenses", keywords: ["cable", "satellite", "telegraph", "radio"] },
        { code: "5020601001", name: "Awards/Rewards Expenses", keywords: ["awards", "rewards", "recognition"] },
        { code: "5020601002", name: "Rewards and Incentives", keywords: ["rewards", "incentives"] },
        { code: "5020602000", name: "Prizes", keywords: ["prizes", "awards"] },
        { code: "5020701002", name: "Survey Expenses", keywords: ["survey", "research"] },
        { code: "5020702002", name: "Research, Exploration and Development Expenses", keywords: ["research", "exploration", "development", "r&d"] },
        { code: "5021101000", name: "Legal Services", keywords: ["legal", "services", "lawyer", "attorney"] },
        { code: "5021102000", name: "Auditing Services", keywords: ["auditing", "services", "audit"] },
        { code: "5021103002", name: "Consultancy Services", keywords: ["consultancy", "services", "consultant"] },
        { code: "5021199000", name: "Other Professional Services", keywords: ["other", "professional", "services"] },
        { code: "5021201000", name: "Environment/Sanitary Services", keywords: ["environment", "sanitary", "services", "cleaning"] },
        { code: "5021202000", name: "Janitorial Services", keywords: ["janitorial", "services", "cleaning"] },
        { code: "5021203000", name: "Security Services", keywords: ["security", "services", "guard"] },
        { code: "5021299099", name: "Other General Services", keywords: ["other", "general", "services"] },
        { code: "5021303005", name: "Repairs and Maintenance - Power Supply Systems", keywords: ["repairs", "maintenance", "power", "supply", "systems", "r&m", "r & m", "rm"] },
        { code: "5021304001", name: "Repairs and Maintenance - Buildings", keywords: ["repairs", "maintenance", "buildings", "r&m", "r & m", "rm"] },
        { code: "5021304002", name: "Repairs and Maintenance - School Buildings", keywords: ["repairs", "maintenance", "school", "buildings", "r&m", "r & m", "rm"] },
        { code: "5021304099", name: "Repairs and Maintenance - Other Structures", keywords: ["repairs", "maintenance", "other", "structures", "r&m", "r & m", "rm"] },
        { code: "5021305001", name: "Repairs and Maintenance - Machinery", keywords: ["repairs", "maintenance", "machinery", "r&m", "r & m", "rm"] },
        { code: "5021305002", name: "Repairs and Maintenance - Office Equipment", keywords: ["repairs", "maintenance", "office", "equipment", "r&m", "r & m", "rm"] },
        { code: "5021305003", name: "Repairs and Maintenance - ICT Equipment", keywords: ["repairs", "maintenance", "ict", "equipment", "computer", "r&m", "r & m", "rm"] },
        { code: "5021306001", name: "Repairs and Maintenance - Motor Vehicles", keywords: ["repairs", "maintenance", "motor", "vehicles", "car", "r&m", "r & m", "rm"] },
        { code: "5021307000", name: "Repairs and Maintenance - Furniture and Fixtures", keywords: ["repairs", "maintenance", "furniture", "fixtures", "r&m", "r & m", "rm"] },
        { code: "5021501001", name: "Taxes, Duties and Licenses", keywords: ["taxes", "duties", "licenses"] },
        { code: "5021502000", name: "Fidelity Bond Premiums", keywords: ["fidelity", "bond", "premiums"] },
        { code: "5021503000", name: "Insurance Expenses", keywords: ["insurance", "expenses"] },
        { code: "5021601000", name: "Labor and Wages", keywords: ["labor", "wages", "workers"] },
        { code: "5029901000", name: "Advertising Expenses", keywords: ["advertising", "expenses", "marketing"] },
        { code: "5029902000", name: "Printing and Publication Expenses", keywords: ["printing", "publication", "expenses"] },
        { code: "5029903000", name: "Representation Expenses", keywords: ["representation", "expenses"] },
        { code: "5029904000", name: "Transportation and Delivery Expenses", keywords: ["transportation", "delivery", "expenses", "shipping"] },
        { code: "5029905001", name: "Rents - Building and Structures", keywords: ["rents", "building", "structures", "rental"] },
        { code: "5029905003", name: "Rents - Motor Vehicles", keywords: ["rents", "motor", "vehicles", "car", "rental"] },
        { code: "5029905004", name: "Rents - Equipment", keywords: ["rents", "equipment", "rental"] },
        { code: "5029906000", name: "Membership Dues and Contributions to Organizations", keywords: ["membership", "dues", "contributions", "organizations"] },
        { code: "5029907001", name: "ICT Software Subscription", keywords: ["ict", "software", "subscription"] },
        { code: "5029907004", name: "Library and Other Reading Materials Subscription Expenses", keywords: ["library", "reading", "materials", "subscription"] },
        { code: "5029908000", name: "Donations", keywords: ["donations", "charitable"] },
        { code: "5029999001", name: "Website Maintenance", keywords: ["website", "maintenance", "web"] },
        { code: "5029999099", name: "Other Maintenance and Operating Expenses", keywords: ["other", "maintenance", "operating", "expenses"] }
    ],
    
    // Capital Outlays (5060000000)
    "capital_outlays": [
        { code: "5060401001", name: "Land", keywords: ["land", "property"] },
        { code: "5060402001", name: "Aquaculture Structures", keywords: ["aquaculture", "structures"] },
        { code: "5060402002", name: "Reforestation Projects", keywords: ["reforestation", "projects", "trees"] },
        { code: "5060403001", name: "Road Networks", keywords: ["road", "networks", "infrastructure"] },
        { code: "5060403002", name: "Flood Control Systems", keywords: ["flood", "control", "systems"] },
        { code: "5060403004", name: "Water Supply Systems", keywords: ["water", "supply", "systems"] },
        { code: "5060403005", name: "Power Supply Systems", keywords: ["power", "supply", "systems", "electricity"] },
        { code: "5060403006", name: "Communications Networks", keywords: ["communications", "networks"] },
        { code: "5060404001", name: "Buildings", keywords: ["buildings", "construction"] },
        { code: "5060404002", name: "School Buildings", keywords: ["school", "buildings", "construction"] },
        { code: "5060404003", name: "Hospitals and Health Centers", keywords: ["hospitals", "health", "centers"] },
        { code: "5060405001", name: "Machinery", keywords: ["machinery", "equipment"] },
        { code: "5060405002", name: "Office Equipment", keywords: ["office", "equipment", "furniture"] },
        { code: "5060405003", name: "Information and Communication Technology Equipment", keywords: ["ict", "equipment", "computer", "technology"] },
        { code: "5060405007", name: "Communications Equipment", keywords: ["communications", "equipment"] },
        { code: "5060405010", name: "Military, Police and Security Equipment", keywords: ["military", "police", "security", "equipment"] },
        { code: "5060405011", name: "Medical Equipment", keywords: ["medical", "equipment"] },
        { code: "5060405012", name: "Printing Equipment", keywords: ["printing", "equipment"] },
        { code: "5060405013", name: "Sports Equipment", keywords: ["sports", "equipment"] },
        { code: "5060405014", name: "Technical and Scientific Equipment", keywords: ["technical", "scientific", "equipment", "laboratory"] },
        { code: "5060405015", name: "ICT Software", keywords: ["ict", "software", "computer", "program"] },
        { code: "5060405099", name: "Other Machinery and Equipment", keywords: ["other", "machinery", "equipment"] },
        { code: "5060406001", name: "Motor Vehicles", keywords: ["motor", "vehicles", "car", "truck"] },
        { code: "5060406003", name: "Aircrafts and Aircrafts Ground Equipment", keywords: ["aircrafts", "ground", "equipment", "plane"] },
        { code: "5060406004", name: "Watercrafts", keywords: ["watercrafts", "boat", "ship"] },
        { code: "5060406099", name: "Other Transportation Equipment", keywords: ["other", "transportation", "equipment"] },
        { code: "5060407001", name: "Furniture and Fixtures", keywords: ["furniture", "fixtures"] },
        { code: "5060407002", name: "Books", keywords: ["books", "library"] },
        { code: "5060409001", name: "Work/Zoo Animals", keywords: ["work", "zoo", "animals"] },
        { code: "5060409099", name: "Other Property, Plant and Equipment", keywords: ["other", "property", "plant", "equipment"] },
        { code: "5060602000", name: "Computer Software", keywords: ["computer", "software", "program"] }
    ]
};

// Function to search UACS codes based on category and keywords
function searchUACSCode(category, searchText) {
    if (!searchText || searchText.length < 2) return [];
    
    const categoryKey = getCategoryKey(category);
    const codes = UACS_CODES[categoryKey] || [];
    const searchLower = searchText.toLowerCase().trim();
    
    // Normalize search text for better matching
    // Handle various formats: "r&m", "r & m", "r and m", "rm"
    // Also normalize dashes: both "-" and "–" (em dash)
    const normalizedSearch = searchLower
        .replace(/\s*&\s*/g, '&')      // "r & m" -> "r&m"
        .replace(/\s+and\s+/g, '&')    // "r and m" -> "r&m"
        .replace(/–/g, '-')             // em dash to regular dash
        .replace(/\s*-\s*/g, ' ')       // normalize dashes to spaces for matching
        .replace(/\s+/g, ' ')           // normalize multiple spaces
        .trim();
    
    return codes.filter(item => {
        // Normalize the item name for comparison
        const normalizedName = item.name.toLowerCase()
            .replace(/–/g, '-')
            .replace(/\s*-\s*/g, ' ')
            .replace(/\s+/g, ' ');
        
        // Check if search text matches the name
        const nameMatch = normalizedName.includes(normalizedSearch) || 
                         item.name.toLowerCase().includes(searchLower);
        
        const keywordMatch = item.keywords.some(keyword => {
            const normalizedKeyword = keyword.toLowerCase()
                .replace(/\s*&\s*/g, '&')
                .replace(/\s+and\s+/g, '&')
                .replace(/–/g, '-')
                .replace(/\s*-\s*/g, ' ')
                .replace(/\s+/g, ' ');
            
            // Try multiple matching strategies
            return normalizedKeyword.includes(normalizedSearch) || 
                   keyword.toLowerCase().includes(searchLower) ||
                   normalizedKeyword === normalizedSearch ||
                   // Special handling for abbreviations like "rm" matching "r&m"
                   (normalizedSearch.replace(/[^a-z0-9]/g, '') === normalizedKeyword.replace(/[^a-z0-9]/g, ''));
        });
        
        const codeMatch = item.code.replace(/\s/g, '').includes(searchLower.replace(/\s/g, ''));
        
        return nameMatch || keywordMatch || codeMatch;
    }).slice(0, 10); // Limit to 10 results
}

// Map category names to keys
function getCategoryKey(category) {
    if (category.includes('PERSONAL SERVICES')) {
        return 'personnel_services';
    } else if (category.includes('Maintenance')) {
        return 'mooe';
    } else if (category.includes('Capital Outlay')) {
        return 'capital_outlays';
    }
    return 'mooe'; // default
}

// Function to format UACS code for display: 5 02 02 010 00
function formatUACSCode(code) {
    if (!code) return '';
    // Remove all spaces first
    const clean = code.replace(/\s/g, '');
    
    // Format as: 5 02 02 010 00 (1 digit, 2 digits, 2 digits, 3 digits, 2 digits)
    if (clean.length === 10) {
        return `${clean[0]} ${clean.substring(1,3)} ${clean.substring(3,5)} ${clean.substring(5,8)} ${clean.substring(8,10)}`;
    }
    return code; // Return as-is if not 10 digits
}
