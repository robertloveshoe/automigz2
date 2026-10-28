<?php
require __DIR__ . '/lib.php';
start_session();
$u = current_user();
$isAdmin = $u && ($u['role'] ?? 'user') === 'admin';
if (!$isAdmin) { http_response_code(403); }
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Automigz Admin</title>
<style>
*{box-sizing:border-box;font-family:'Segoe UI',Arial,sans-serif;margin:0;padding:0}
body{background:#f5f5f5;color:#222}
header{background:#fff;border-bottom:5px solid #d32f2f;padding:12px 24px;display:flex;align-items:center;justify-content:space-between}
header b{color:#d32f2f;font-size:18px;font-style:italic}header a,header button{color:#333;font-size:13px;font-weight:600;text-decoration:none;background:none;border:0;cursor:pointer;margin-left:18px}
main{max-width:1300px;margin:0 auto;padding:24px}
.denied{max-width:420px;margin:80px auto;background:#fff;padding:30px;border-radius:10px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,.1)}
.denied a{display:inline-block;margin-top:14px;background:#d32f2f;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none;font-weight:700}
.sections{display:flex;gap:8px;margin-bottom:20px;border-bottom:2px solid #e3e3e3}
.sec{padding:10px 22px;border:0;background:none;font-weight:700;font-size:14px;color:#777;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px}
.sec.on{color:#d32f2f;border-bottom-color:#d32f2f}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat{background:#fff;border-radius:8px;padding:16px 18px;box-shadow:0 2px 8px rgba(0,0,0,.07)}
.stat b{display:block;font-size:26px;color:#d32f2f}.stat span{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#777}
.bar{display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
.tab{padding:7px 16px;border:1px solid #ccc;border-radius:18px;background:#fff;font-weight:600;font-size:13px;cursor:pointer}.tab.on{background:#d32f2f;border-color:#d32f2f;color:#fff}
.bar input.search{margin-left:auto;padding:8px 12px;border:1px solid #ccc;border-radius:5px;min-width:240px}
.wrap{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #eee;vertical-align:top}
th{background:#fafafa;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#666;white-space:nowrap}
.tot{font-weight:800;color:#d32f2f;white-space:nowrap}small{color:#777;display:block}
.badge{font-size:11px;font-weight:800;padding:3px 10px;border-radius:12px;white-space:nowrap}.a{background:#e4f5e8;color:#1c9c3c}.c{background:#fdecec;color:#d32f2f}.adm{background:#fff0d6;color:#b36b00}.usr{background:#eef1f6;color:#4a5568}
tr.x td{opacity:.6}.x .tot{text-decoration:line-through}
.btn{border:2px solid #d32f2f;color:#d32f2f;background:#fff;border-radius:5px;padding:5px 12px;font-weight:700;cursor:pointer;font-size:12px}.btn:hover{background:#d32f2f;color:#fff}
.btn.solid{background:#d32f2f;color:#fff;padding:8px 16px;font-size:13px}.btn.solid:hover{background:#b71c1c}
.btn.grey{border-color:#888;color:#555}.btn.grey:hover{background:#555;border-color:#555;color:#fff}
.acts{white-space:nowrap}.acts .btn{margin-right:4px}
.empty{text-align:center;color:#777;padding:40px}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
.modal.show{display:flex}
.card{background:#fff;border-radius:10px;padding:26px;width:100%;max-width:460px;max-height:92vh;overflow-y:auto;box-shadow:0 12px 40px rgba(0,0,0,.25)}
.card.wide{max-width:680px}
.card h3{margin-bottom:12px}
.card label{display:block;font-weight:600;font-size:12px;margin:12px 0 4px}
.card input,.card select{width:100%;padding:9px 12px;border:1px solid #ccc;border-radius:5px;font-size:14px}
.card input:disabled,.card select:disabled{background:#f1f1f1;color:#777}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.err{color:#b00020;font-size:13px;margin-top:10px;min-height:16px}
.hint{font-size:11px;color:#777;margin-top:4px}
.foot{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
.info{display:grid;grid-template-columns:120px 1fr;gap:6px 12px;font-size:14px;margin:8px 0 16px}.info span{color:#777;font-weight:600}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#222;color:#fff;padding:10px 20px;border-radius:6px;font-size:13px;opacity:0;pointer-events:none;transition:opacity .25s;z-index:60}.toast.show{opacity:1}
@media(max-width:800px){.stats{grid-template-columns:repeat(2,1fr)}.row2{grid-template-columns:1fr}}
</style></head><body>
<header><b>AUTOMIGZ Admin</b><div><a href="index.php">← Back to site</a><?php if ($isAdmin): ?><button id="out">Log out</button><?php endif; ?></div></header>
<?php if (!$isAdmin): ?>
<div class="denied"><h2>Admins only</h2><p style="margin-top:8px;color:#666"><?= $u ? 'This account is not an admin.' : 'Please log in with your admin account on the main site first.' ?></p><a href="index.php#/auth">Go to the site</a></div>
<?php else: ?>
<main>
  <div class="sections">
    <button class="sec on" data-s="bookings">Bookings</button>
    <button class="sec" data-s="users">Users</button>
  </div>

  <section id="sec-bookings">
    <div class="stats">
      <div class="stat"><b id="sAct">–</b><span>Active bookings</span></div>
      <div class="stat"><b id="sRev">–</b><span>Active revenue</span></div>
      <div class="stat"><b id="sCan">–</b><span>Cancelled</span></div>
      <div class="stat"><b id="sCus">–</b><span>Customers</span></div>
    </div>
    <div class="bar">
      <button class="tab on" data-f="all">All</button><button class="tab" data-f="active">Active</button><button class="tab" data-f="cancelled">Cancelled</button>
      <input class="search" id="q" placeholder="Search customer, car, email, place…">
    </div>
    <div class="wrap"><table>
      <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Handover</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody id="rows"><tr><td colspan="8" class="empty">Loading…</td></tr></tbody>
    </table></div>
  </section>

  <section id="sec-users" style="display:none">
    <div class="bar">
      <button class="tab on" data-r="all">All</button><button class="tab" data-r="user">Customers</button><button class="tab" data-r="admin">Admins</button>
      <input class="search" id="uq" placeholder="Search name, email, mobile…">
      <button class="btn solid" id="addUser">+ Add user</button>
    </div>
    <div class="wrap"><table>
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Bookings</th><th>Joined</th><th></th></tr></thead>
      <tbody id="urows"><tr><td colspan="8" class="empty">Loading…</td></tr></tbody>
    </table></div>
  </section>
</main>

<div class="modal" id="formModal"><div class="card">
  <h3 id="formTitle">Add user</h3>
  <div class="row2"><div><label for="fFirst">First name</label><input id="fFirst" maxlength="60"></div><div><label for="fLast">Last name</label><input id="fLast" maxlength="60"></div></div>
  <label for="fEmail">Email (used to log in)</label><input id="fEmail" type="email" maxlength="120">
  <label for="fMobile">Mobile number</label><input id="fMobile" maxlength="20" placeholder="09XX-XXX-XXXX">
  <label for="fRole">Role</label><select id="fRole"><option value="user">Customer</option><option value="admin">Admin</option></select>
  <label for="fPass" id="fPassLbl">Password</label><input id="fPass" type="password" autocomplete="new-password">
  <div class="hint" id="fPassHint"></div>
  <div class="err" id="fErr"></div>
  <div class="foot"><button class="btn grey" id="fCancel" type="button">Close</button><button class="btn solid" id="fSave" type="button">Save</button></div>
</div></div>

<div class="modal" id="viewModal"><div class="card wide" id="viewCard"></div></div>
<div class="toast" id="toast"></div>

<script>
const CSRF = <?= json_encode($_SESSION['csrf']) ?>;
const ME = <?= (int)$u['id'] ?>;
const peso = n => "₱" + Number(n).toLocaleString();
const esc = v => String(v == null ? "" : v).replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const $ = id => document.getElementById(id);
let all = [], filter = "all", users = [], roleFilter = "all", editingId = null;

async function api(action, data){
  const opts = data === undefined ? {credentials:"same-origin"} :
    {method:"POST", credentials:"same-origin", headers:{"Content-Type":"application/json","X-CSRF-Token":CSRF}, body:JSON.stringify(data)};
  try { return await (await fetch("api.php?action=" + action, opts)).json(); }
  catch(e){ return {ok:false, error:"Cannot reach the server."}; }
}
function toast(msg){ const t = $("toast"); t.textContent = msg; t.classList.add("show"); setTimeout(() => t.classList.remove("show"), 2400); }

document.querySelectorAll(".sec").forEach(b => b.onclick = () => {
  document.querySelectorAll(".sec").forEach(x => x.classList.remove("on")); b.classList.add("on");
  $("sec-bookings").style.display = b.dataset.s === "bookings" ? "" : "none";
  $("sec-users").style.display    = b.dataset.s === "users" ? "" : "none";
});

async function load(){
  const r = await api("admin_bookings");
  if (!r.ok){ $("rows").innerHTML = `<tr><td colspan="8" class="empty">${esc(r.error || "Could not load")}</td></tr>`; return; }
  all = r.bookings;
  const act = all.filter(b => b.status !== "cancelled");
  $("sAct").textContent = act.length;
  $("sRev").textContent = peso(act.reduce((s,b) => s + b.total, 0));
  $("sCan").textContent = all.length - act.length;
  $("sCus").textContent = r.customers;
  draw();
}
function draw(){
  const term = $("q").value.trim().toLowerCase();
  const list = all.filter(b => (filter === "all" || (filter === "cancelled") === (b.status === "cancelled")) &&
    (!term || [b.customer.name, b.customer.email, b.customer.mobile, b.car, b.address, b.location].join(" ").toLowerCase().includes(term)));
  $("rows").innerHTML = list.length ? list.map(b => {
    const x = b.status === "cancelled";
    return `<tr class="${x ? "x" : ""}">
      <td>${b.id}</td>
      <td><b>${esc(b.customer.name)}</b><small>${esc(b.customer.email)}</small><small>${esc(b.phone || b.customer.mobile)}</small></td>
      <td>${b.qty} × ${esc(b.car)}<small>${esc(b.color.n)}</small></td>
      <td>${esc(b.pickDate)} → ${esc(b.retDate)}<small>${b.days} day(s)</small></td>
      <td>${b.mode === "deliver" ? "🚚 " + esc(b.address) + `<small>Delivery fee ${peso(b.fee)}</small>` : "📍 " + esc(b.location)}</td>
      <td class="tot">${peso(b.total)}</td>
      <td><span class="badge ${x ? "c" : "a"}">${x ? "Cancelled" : "Active"}</span></td>
      <td>${x ? "" : `<button class="btn" onclick="cancelIt(${b.id})">Cancel</button>`}</td></tr>`;
  }).join("") : `<tr><td colspan="8" class="empty">No bookings found.</td></tr>`;
}
async function cancelIt(id){
  if (!confirm("Cancel booking #" + id + "? The customer will see it as cancelled.")) return;
  const r = await api("admin_cancel", {id});
  if (!r.ok) alert(r.error || "Could not cancel.");
  load(); loadUsers();
}
document.querySelectorAll("#sec-bookings .tab").forEach(t => t.onclick = () => {
  document.querySelectorAll("#sec-bookings .tab").forEach(x => x.classList.remove("on")); t.classList.add("on"); filter = t.dataset.f; draw(); });
$("q").oninput = draw;

async function loadUsers(){
  const r = await api("admin_users");
  if (!r.ok){ $("urows").innerHTML = `<tr><td colspan="8" class="empty">${esc(r.error || "Could not load")}</td></tr>`; return; }
  users = r.users; drawUsers();
}
function drawUsers(){
  const term = $("uq").value.trim().toLowerCase();
  const list = users.filter(u => (roleFilter === "all" || u.role === roleFilter) &&
    (!term || [u.first, u.last, u.first + " " + u.last, u.email, u.mobile].join(" ").toLowerCase().includes(term)));
  $("urows").innerHTML = list.length ? list.map(u => `<tr>
      <td>${u.id}</td>
      <td><b>${esc(u.first)} ${esc(u.last)}</b>${u.id === ME ? "<small>(you)</small>" : ""}</td>
      <td>${esc(u.email)}</td>
      <td>${esc(u.mobile)}</td>
      <td><span class="badge ${u.role === "admin" ? "adm" : "usr"}">${u.role === "admin" ? "Admin" : "Customer"}</span></td>
      <td>${u.bookings}<small>${u.active} active</small></td>
      <td>${u.joined ? esc(new Date(u.joined).toLocaleDateString()) : "–"}</td>
      <td class="acts"><button class="btn grey" onclick="viewUser(${u.id})">View</button><button class="btn" onclick="editUser(${u.id})">Edit</button>${u.id === ME ? "" : `<button class="btn" onclick="deleteUser(${u.id})">Delete</button>`}</td>
    </tr>`).join("") : `<tr><td colspan="8" class="empty">No users found.</td></tr>`;
}
document.querySelectorAll("#sec-users .tab").forEach(t => t.onclick = () => {
  document.querySelectorAll("#sec-users .tab").forEach(x => x.classList.remove("on")); t.classList.add("on"); roleFilter = t.dataset.r; drawUsers(); });
$("uq").oninput = drawUsers;

function openForm(u){
  editingId = u ? u.id : null;
  $("formTitle").textContent = u ? "Edit user #" + u.id : "Add user";
  $("fFirst").value = u ? u.first : ""; $("fLast").value = u ? u.last : "";
  $("fEmail").value = u ? u.email : "";  $("fMobile").value = u ? u.mobile : "";
  $("fRole").value = u ? u.role : "user";
  $("fRole").disabled = !!(u && u.id === ME);
  $("fPass").value = "";
  $("fPassLbl").textContent = u ? "New password (optional)" : "Password";
  $("fPassHint").textContent = u ? "Leave blank to keep the current password." : "8+ characters with upper + lower case and a number or symbol (admins: 10+).";
  $("fErr").textContent = "";
  $("formModal").classList.add("show"); $("fFirst").focus();
}
$("addUser").onclick = () => openForm(null);
function editUser(id){ openForm(users.find(u => u.id === id)); }
$("fCancel").onclick = () => $("formModal").classList.remove("show");
$("fSave").onclick = async () => {
  const data = { first:$("fFirst").value.trim(), last:$("fLast").value.trim(), email:$("fEmail").value.trim(),
                 mobile:$("fMobile").value.trim(), role:$("fRole").value, pass:$("fPass").value };
  if (editingId) data.id = editingId;
  $("fSave").disabled = true;
  const r = await api(editingId ? "admin_user_update" : "admin_user_add", data);
  $("fSave").disabled = false;
  if (!r.ok){ $("fErr").textContent = r.error || "Could not save."; return; }
  $("formModal").classList.remove("show");
  toast(editingId ? "User updated." : "User added.");
  loadUsers(); load();
};

function viewUser(id){
  const u = users.find(x => x.id === id); if (!u) return;
  const mine = all.filter(b => b.userId === id);
  $("viewCard").innerHTML = `
    <h3>${esc(u.first)} ${esc(u.last)} <span class="badge ${u.role === "admin" ? "adm" : "usr"}">${u.role === "admin" ? "Admin" : "Customer"}</span></h3>
    <div class="info"><span>User ID</span><div>#${u.id}</div><span>Email</span><div>${esc(u.email)}</div><span>Mobile</span><div>${esc(u.mobile)}</div>
      <span>Joined</span><div>${u.joined ? esc(new Date(u.joined).toLocaleString()) : "–"}</div>
      <span>Bookings</span><div>${u.bookings} total · ${u.active} active</div></div>
    <b style="font-size:13px">Booking history</b>
    <div class="wrap" style="margin-top:8px;box-shadow:none;border:1px solid #eee"><table>
      <thead><tr><th>#</th><th>Vehicle</th><th>Dates</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>${mine.length ? mine.map(b => `<tr class="${b.status === "cancelled" ? "x" : ""}"><td>${b.id}</td><td>${b.qty} × ${esc(b.car)}<small>${esc(b.color.n)}</small></td>
        <td>${esc(b.pickDate)} → ${esc(b.retDate)}</td><td class="tot">${peso(b.total)}</td>
        <td><span class="badge ${b.status === "cancelled" ? "c" : "a"}">${b.status === "cancelled" ? "Cancelled" : "Active"}</span></td></tr>`).join("")
        : `<tr><td colspan="5" class="empty">No bookings yet.</td></tr>`}</tbody></table></div>
    <div class="foot"><button class="btn grey" onclick="closeView()">Close</button><button class="btn solid" onclick="closeView();editUser(${u.id})">Edit</button></div>`;
  $("viewModal").classList.add("show");
}
function closeView(){ $("viewModal").classList.remove("show"); }
$("viewModal").onclick = e => { if (e.target.id === "viewModal") closeView(); };
$("formModal").onclick = e => { if (e.target.id === "formModal") $("formModal").classList.remove("show"); };

async function deleteUser(id){
  const u = users.find(x => x.id === id); if (!u) return;
  const extra = u.bookings ? `\n\nTheir ${u.bookings} cancelled booking record(s) will be deleted too.` : "";
  if (!confirm(`Delete ${u.first} ${u.last} (${u.email})? This cannot be undone.` + extra)) return;
  const r = await api("admin_user_delete", {id});
  if (!r.ok){ alert(r.error || "Could not delete."); return; }
  toast("User deleted.");
  loadUsers(); load();
}

$("out").onclick = async () => { await api("logout", {}); location.href = "index.php"; };
load(); loadUsers();
</script>
<?php endif; ?>
</body></html>
