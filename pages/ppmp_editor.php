<?php
session_start();

// Allow only logged in dept/offices users
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['offices', 'budget', 'school_admin', 'supply_office'])) {
    header('Location: ../login.php');
    exit;
}

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPMP Editor</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { maroon: '#800000','maroon-dark':'#5a0000' } } } }
    </script>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
    <aside class="w-64 bg-white border-r">
        <div class="p-6 border-b">
            <h2 class="text-2xl font-bold text-maroon">BudgetTrack</h2>
            <p class="text-sm text-gray-600">PPMP Editor</p>
        </div>
        <nav class="mt-6">
            <a href="dept_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">Back to Dashboard</a>
        </nav>
    </aside>
    <main class="flex-1">
        <header class="bg-white border-b px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create PPMP</h1>
                    <p class="text-gray-600">Build your procurement items in a spreadsheet-like editor.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input id="fiscalYear" type="number" min="2000" max="2099" step="1" class="border rounded px-3 py-2 w-28" />
                    <button id="btnLoad" class="px-4 py-2 border rounded hover:bg-gray-50">Load Draft</button>
                    <button id="btnSave" class="px-4 py-2 bg-maroon text-white rounded hover:bg-maroon-dark">Save Draft</button>
                    <button id="btnSubmit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Submit to Budget</button>
                </div>
            </div>
        </header>

        <section class="p-6">
            <div class="bg-white border rounded-xl p-4">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-semibold text-gray-800">Items</h2>
                    <div class="space-x-2">
                        <button id="btnAddRow" class="px-3 py-1 border rounded hover:bg-gray-50">+ Row</button>
                        <button id="btnClear" class="px-3 py-1 border rounded hover:bg-gray-50">Clear</button>
                    </div>
                </div>
                <div class="overflow-auto">
                    <table id="grid" class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left border">Item/Project</th>
                                <th class="px-3 py-2 text-left border">Unit</th>
                                <th class="px-3 py-2 text-right border">Qty</th>
                                <th class="px-3 py-2 text-right border">Unit Cost</th>
                                <th class="px-3 py-2 text-right border">Total</th>
                                <th class="px-3 py-2 text-left border">Semester</th>
                                <th class="px-3 py-2 text-left border">Remarks</th>
                                <th class="px-3 py-2 text-center border">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="gridBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-semibold border">Grand Total</td>
                                <td id="grandTotal" class="px-3 py-2 text-right font-bold border">0.00</td>
                                <td class="border" colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
const userId = <?php echo json_encode($userId); ?>;
const departmentId = <?php echo json_encode($departmentId); ?>;

document.addEventListener('DOMContentLoaded', () => {
  const fy = document.getElementById('fiscalYear');
  const y = new Date().getFullYear();
  fy.value = y;
  addRow();

  document.getElementById('btnAddRow').addEventListener('click', addRow);
  document.getElementById('btnClear').addEventListener('click', clearGrid);
  document.getElementById('btnSave').addEventListener('click', saveDraft);
  document.getElementById('btnLoad').addEventListener('click', loadDraft);
  document.getElementById('btnSubmit').addEventListener('click', submitPPMP);
});

function addRow(data = {}){
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td class="border p-1"><input class="w-full px-2 py-1" value="${data.item||''}"></td>
    <td class="border p-1"><input class="w-full px-2 py-1" value="${data.unit||''}"></td>
    <td class="border p-1 text-right"><input type="number" class="w-full px-2 py-1 text-right qty" min="0" step="1" value="${data.qty||0}" oninput="recalcRow(this)"></td>
    <td class="border p-1 text-right"><input type="number" class="w-full px-2 py-1 text-right unitCost" min="0" step="0.01" value="${data.unit_cost||0}" oninput="recalcRow(this)"></td>
    <td class="border p-1 text-right total">0.00</td>
    <td class="border p-1"><input class="w-full px-2 py-1" value="${(data.semester??data.quarter)||''}" placeholder="1st/2nd"></td>
    <td class="border p-1"><input class="w-full px-2 py-1" value="${data.deducted_from_categories || data.remarks || ''}" placeholder="${data.deducted_from_categories ? 'Deducted from: ' + data.deducted_from_categories : 'Remarks'}" ${data.deducted_from_categories ? 'readonly style="background-color: #fef3c7; font-weight: 500;"' : ''}></td>
    <td class="border p-1 text-center"><button class="px-2 py-1 text-red-600" onclick="this.closest('tr').remove(); recalcGrand();">Delete</button></td>
  `;
  document.getElementById('gridBody').appendChild(tr);
  recalcRow(tr.querySelector('.qty'));
}

function recalcRow(el){
  const tr = el.closest('tr');
  const qty = parseFloat(tr.querySelector('.qty').value||0);
  const unit = parseFloat(tr.querySelector('.unitCost').value||0);
  const total = qty * unit;
  tr.querySelector('.total').textContent = total.toFixed(2);
  recalcGrand();
}

function recalcGrand(){
  let sum = 0;
  document.querySelectorAll('#gridBody .total').forEach(td=>{ sum += parseFloat(td.textContent||0); });
  document.getElementById('grandTotal').textContent = sum.toFixed(2);
}

function clearGrid(){ document.getElementById('gridBody').innerHTML=''; addRow(); recalcGrand(); }

function serializeGrid(){
  const rows = [];
  document.querySelectorAll('#gridBody tr').forEach(tr=>{
    const cells = tr.querySelectorAll('td');
    rows.push({
      item: cells[0].querySelector('input').value.trim(),
      unit: cells[1].querySelector('input').value.trim(),
      qty: parseFloat(cells[2].querySelector('input').value||0),
      unit_cost: parseFloat(cells[3].querySelector('input').value||0),
      total: parseFloat(cells[4].textContent||0),
      semester: cells[5].querySelector('input').value.trim(),
      remarks: cells[6].querySelector('input').value.trim(),
    });
  });
  return { fiscal_year: parseInt(document.getElementById('fiscalYear').value||new Date().getFullYear(),10), rows };
}

async function saveDraft(){
  const payload = serializeGrid();
  const res = await fetch('../ajax/save_ppmp_draft.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const data = await res.json();
  alert(data.success ? 'Draft saved' : (data.message||'Failed to save'));
}

async function loadDraft(){
  const fiscal_year = parseInt(document.getElementById('fiscalYear').value||new Date().getFullYear(),10);
  const res = await fetch('../ajax/load_ppmp_draft.php?fiscal_year='+encodeURIComponent(fiscal_year));
  if(!res.ok){ alert('No draft found'); return; }
  const data = await res.json();
  document.getElementById('gridBody').innerHTML='';
  (data.rows||[]).forEach(r=> addRow(r));
  recalcGrand();
}

async function submitPPMP(){
  if(!confirm('Submit this PPMP to the Budget Office? You will no longer be able to edit this draft.')) return;
  const payload = serializeGrid();
  const res = await fetch('../ajax/submit_ppmp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const data = await res.json();
  if(data.success){
    alert('PPMP submitted successfully');
  }else{
    alert(data.message||'Submission failed');
  }
}
</script>

</body>
</html>


