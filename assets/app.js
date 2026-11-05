'use strict';
const $ = (s, root = document) => root.querySelector(s);
const $$ = (s, root = document) => [...root.querySelectorAll(s)];
const state = { user: null, csrf: '', categories: [], filter: 'all', query: '', category: '', page: 'browse', afterAuth: null, request: 0 };
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const paths = {
  grid:'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
  folder:'<path d="M3 7V5a2 2 0 0 1 2-2h5l3 3h6a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 9h18"/>',
  heart:'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
  search:'<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
  pin:'<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
  plus:'<path d="M12 5v14M5 12h14"/>',
  clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  shield:'<path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6l8-4Z"/><path d="m8 12 3 3 5-6"/>',
  check:'<path d="m5 12 4 4L19 6"/>'
};
function icon(name){ return `<svg class="icon" aria-hidden="true" viewBox="0 0 24 24">${paths[name] || paths.grid}</svg>`; }
function hydrateIcons(){ $$('[data-icon]').forEach(el => el.innerHTML = icon(el.dataset.icon)); }
function art(item){
  let shape = '';
  const title = (item.title || '').toLowerCase();
  if(item.category === 'Electronics') shape = '<path d="M40 100V74a40 40 0 0 1 80 0v26" fill="none" stroke="#293d3b" stroke-width="14"/><path d="M43 79V68a37 37 0 0 1 74 0v11" fill="none" stroke="#5a6f69" stroke-width="5"/><rect x="30" y="83" width="27" height="47" rx="12" fill="#344b46"/><rect x="103" y="83" width="27" height="47" rx="12" fill="#344b46"/><rect x="48" y="87" width="12" height="37" rx="5" fill="#1e302c"/><rect x="100" y="87" width="12" height="37" rx="5" fill="#1e302c"/>';
  else if(item.category === 'Bags & wallets') shape = '<path d="M64 34V24q16-13 32 0v10" fill="none" stroke="#364b37" stroke-width="6"/><path d="M43 65q-12 24-8 58m82-58q12 24 8 58" fill="none" stroke="#425e45" stroke-width="9"/><rect x="41" y="31" width="78" height="111" rx="27" fill="#56765a" stroke="#39513b" stroke-width="2"/><path d="M48 66q32-14 64 0" fill="none" stroke="#9caf82" stroke-width="2"/><rect x="52" y="89" width="56" height="37" rx="10" fill="#658266" stroke="#39513b" stroke-width="2"/><path d="M57 98h46" stroke="#b5bd8d" stroke-width="2"/><rect x="69" y="54" width="22" height="12" rx="2" fill="#c1b993"/><path d="M102 98v9" stroke="#d6c699" stroke-width="3"/>';
  else if(item.category === 'Keys') shape = '<g transform="rotate(-25 80 80)"><ellipse cx="80" cy="42" rx="29" ry="25" fill="none" stroke="#a3aaa3" stroke-width="5"/><path d="M62 55h15v76H62v-13H48v-12h14V95H51V83h11Z" fill="#b9bdb4" stroke="#7a8379" stroke-width="2"/><rect x="83" y="49" width="36" height="58" rx="8" fill="#e5be62" stroke="#bf9c4f" stroke-width="2"/><circle cx="101" cy="59" r="5" fill="#f2ead6"/><path d="M100 30v25" stroke="#9b9f95" stroke-width="4"/></g>';
  else if(item.category === 'Clothing') shape = '<path d="m57 37-29 24-12 50 23 7 11-31v57h60V87l11 31 23-7-12-50-29-24Z" fill="#9ba8a6" stroke="#71857f" stroke-width="2"/><path d="M57 37q23-34 46 0l-13 23H70Z" fill="#b1bab6" stroke="#71857f" stroke-width="2"/><path d="M80 58v84M69 59v27M91 59v27" stroke="#627972" stroke-width="2"/><path d="m55 106 14 14m36-14-14 14" stroke="#71857f" stroke-width="2"/>';
  else if(item.category === 'Documents') shape = '<g transform="rotate(-12 80 80)"><rect x="39" y="26" width="85" height="115" rx="5" fill="#d7c59b" stroke="#aa9874" stroke-width="2"/><rect x="44" y="26" width="9" height="115" fill="#567166"/><rect x="67" y="49" width="42" height="31" rx="2" fill="#eee8d4"/><path d="M74 59h28m-28 9h21" stroke="#b8ad92" stroke-width="2"/><path d="m112 94-7 48" stroke="#304f45" stroke-width="6"/><path d="m104 143 1 6 3-5" fill="#d2b689"/></g>';
  else if(title.includes('bottle')) shape = '<rect x="62" y="22" width="36" height="19" rx="5" fill="#a5b4b5"/><rect x="57" y="37" width="46" height="104" rx="14" fill="#577f91" stroke="#426575" stroke-width="2"/><path d="M67 54v67" stroke="#93b3bf" stroke-width="4" stroke-linecap="round"/><circle cx="81" cy="91" r="14" fill="#e3e9da"/><path d="m70 96 9-15 5 8 4-4 5 11" fill="#839e82"/>';
  else shape = '<rect x="35" y="51" width="90" height="79" rx="7" fill="#cfb386" stroke="#a78b65" stroke-width="2"/><path d="m35 51 45-23 45 23-45 23Z" fill="#e0c9a2" stroke="#a78b65" stroke-width="2"/><path d="M80 75v55m-23-91 46 24v20" fill="none" stroke="#b7a27b" stroke-width="7"/><path d="m59 98 10 10 27-26" fill="none" stroke="#fff6e1" stroke-width="5"/>';
  return `<svg class="object-art" role="img" aria-label="${esc(item.category)} illustration" viewBox="0 0 160 160">${shape}</svg>`;
}
const dateLabel = date => new Date(date + 'T12:00:00').toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
function visual(item){ return item.image && /^uploads\/[a-f0-9]+\.(jpg|png|webp)$/.test(item.image) ? `<img src="${esc(item.image)}" alt="${esc(item.title)}" loading="lazy">` : art(item); }
function badge(item){ const status = item.status === 'open' ? item.type : item.status; return `<span class="badge ${esc(status)}">${({lost:'Lost',found:'Found',returned:'Reunited',hidden:'Hidden'})[status] || esc(status)}</span>`; }
function card(item){ return `<button class="item-card" data-item="${item.id}" aria-label="View ${esc(item.title)}"><div class="item-visual tone-${item.id % 6}">${badge(item)}${visual(item)}${item.is_demo ? '<span class="demo-label">Demo</span>' : ''}</div><div class="card-body"><span class="card-category">${esc(item.category)}</span><h3>${esc(item.title)}</h3><div class="card-location">${icon('pin')}${esc(item.location)}</div><div class="card-bottom"><span>${dateLabel(item.event_date)}</span><span class="card-arrow">↗</span></div></div></button>`; }
function empty(title, text, action = '') { return `<div class="empty">${icon('search')}<h3>${esc(title)}</h3><p>${esc(text)}</p>${action}</div>`; }
async function api(action, data = null, params = {}) {
  const options = {headers:{'Accept':'application/json'},credentials:'same-origin'};
  if(data !== null){ options.method = 'POST'; options.headers['X-CSRF-Token'] = state.csrf; if(data instanceof FormData) options.body = data; else {options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(data);} }
  const response = await fetch('api.php?' + new URLSearchParams({action,...params}),options);
  let result;
  try { result = await response.json(); } catch { throw new Error('The server could not respond. Make sure Apache and MySQL are running, then refresh.'); }
  if(!response.ok || !result.ok) throw new Error(result.error || 'Something went wrong. Please try again.');
  if(result.csrf) state.csrf = result.csrf;
  return result;
}
let toastTimer;
function toast(message){ $('#toast').textContent = message; $('#toast').classList.add('show'); clearTimeout(toastTimer); toastTimer = setTimeout(()=>$('#toast').classList.remove('show'),4500); }
function updateStats(stats){ if(!stats) return; for(const key of ['lost','found','returned']) $('#stat-'+key).textContent = stats[key]; }
function account(){ $('#account').innerHTML = state.user ? `<span class="avatar" aria-hidden="true">${esc(state.user.name[0].toUpperCase())}</span><span class="account-name">${esc(state.user.name)}</span><button class="btn btn-outline btn-small" data-logout>Sign out</button>` : '<button class="btn btn-outline btn-small" data-auth="login">Sign in</button><button class="btn btn-dark btn-small" data-auth="register">Join community <span>↗</span></button>'; $('#admin-link').hidden = state.user?.role !== 'admin'; }
function showModal(html){ $('#modal-content').innerHTML = `<div class="modal-inner"><button class="modal-close" data-close aria-label="Close dialog">×</button>${html}</div>`; if(!$('#modal').open) $('#modal').showModal(); $('#modal').scrollTop = 0; const heading=$('.modal-title', $('#modal')); if(heading) {heading.id='dialog-title'; $('#modal').setAttribute('aria-labelledby','dialog-title');} }
function closeModal(){ $('#modal').close(); }
function auth(mode = 'login', callback = null){
  if(callback) state.afterAuth = callback;
  const register = mode === 'register';
  showModal(`<div class="eyebrow">WELCOME TO REUNITE</div><h2 class="modal-title">${register?'A little community starts here.':'Good to see you again.'}</h2><p class="modal-subtitle">${register?'Create an account to post items and help bring them home.':'Sign in to manage your reports and reconnect with your belongings.'}</p><form id="auth-form" data-mode="${mode}">${register?'<label class="field"><span>Your name</span><input name="name" autocomplete="name" minlength="2" maxlength="80" placeholder="How should we call you?" required></label>':''}<label class="field"><span>Email address</span><input type="email" name="email" autocomplete="email" maxlength="190" placeholder="you@example.com" required></label><label class="field"><span>Password</span><input type="password" name="password" autocomplete="${register?'new-password':'current-password'}" minlength="${register?8:1}" maxlength="72" placeholder="${register?'At least 8 characters':'Your password'}" required></label><div class="form-error" hidden></div><button class="btn btn-dark form-submit" type="submit">${register?'Create account':'Sign in'} <span>↗</span></button></form><p class="auth-switch">${register?'Already part of the community?':'New around here?'} <button data-auth="${register?'login':'register'}">${register?'Sign in':'Create an account'}</button></p>`);
}
function report(type = 'lost'){
  if(!state.user){auth('login',()=>report(type));return;}
  const today = new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Kolkata',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
  showModal(`<div class="eyebrow">LET YOUR COMMUNITY HELP</div><h2 class="modal-title">Report an item.</h2><p class="modal-subtitle">A few details can make all the difference. Keep contact details and the secret answer out of your public description.</p><form id="report-form"><div class="form-grid"><label class="field"><span>I have…</span><select name="type"><option value="lost" ${type==='lost'?'selected':''}>Lost something</option><option value="found" ${type==='found'?'selected':''}>Found something</option></select></label><label class="field"><span>Category</span><select name="category" required><option value="">Choose a category</option>${state.categories.map(c=>`<option>${esc(c)}</option>`).join('')}</select></label><label class="field full"><span>Item name</span><input name="title" required minlength="3" maxlength="120" placeholder="e.g. Forest green backpack"></label><label class="field full"><span>Description</span><textarea name="description" minlength="10" maxlength="2000" required placeholder="Describe the item, where you last saw it, and anything helpful."></textarea></label><label class="field"><span>Location</span><input name="location" required minlength="2" maxlength="120" placeholder="e.g. Central library"></label><label class="field"><span>Date lost or found</span><input type="date" name="event_date" required value="${today}" max="${today}"></label><label class="field full"><span>Photo (optional)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, or WebP · Maximum 5 MB</small></label><label class="field full"><span>Verification question</span><input name="verification_question" minlength="5" maxlength="200" required placeholder="e.g. What is attached to the zipper?"><small>Ask about a detail only the owner or someone holding the item would know.</small></label><label class="field full"><span>Secret answer</span><input name="verification_answer" minlength="1" maxlength="100" required placeholder="e.g. a star keychain" autocomplete="off"><small>Stored privately. Answers are not case-sensitive.</small></label></div><div class="form-error" hidden></div><button class="btn btn-dark form-submit" type="submit">Post to the community board <span>↗</span></button></form>`);
}
async function browse(){
  const request = ++state.request;
  try{
    const result = await api('items',null,{q:state.query,category:state.category,type:['lost','found'].includes(state.filter)?state.filter:'',status:state.filter==='returned'?'returned':'open'});
    if(request !== state.request) return;
    $('#items-grid').innerHTML = result.items.length ? result.items.map(card).join('') : empty('Nothing here just yet.','Try a different search or category. You can also post an item and let the community help.','<button class="btn btn-light" data-reset>Clear filters</button>');
    $('#item-count').textContent = result.items.length; updateStats(result.stats);
  } catch(error){ if(request === state.request) $('#items-grid').innerHTML = empty('The board is taking a moment.',error.message,'<button class="btn btn-light" data-retry>Try again</button>'); }
}
async function itemDetail(id){
  showModal('<h2 class="modal-title">Opening item…</h2><div class="spinner"></div>');
  try{
    const result=await api('item',null,{id}); const item=result.item;
    const manage=state.user && (state.user.id===item.user_id || state.user.role==='admin');
    const canClaim=state.user && !manage && item.status==='open' && !item.is_demo;
    showModal(`${badge(item)}<div class="detail-visual tone-${item.id%6}">${visual(item)}</div><span class="card-category">${esc(item.category)}${item.is_demo?' · DEMO POST':''}</span><h2 class="modal-title">${esc(item.title)}</h2><div class="detail-meta"><span>${icon('pin')}${esc(item.location)}</span><span>${icon('clock')}${dateLabel(item.event_date)}</span><span>Posted by ${esc(item.owner_name)}</span></div><p class="detail-description">${esc(item.description)}</p>${item.is_demo?'<div class="private-note">This is a sample listing to show how the board works. Create your own report to try the real claim process.</div>':''}${manage && item.status==='open'?`<div class="detail-actions"><button class="btn btn-dark" data-return="${item.id}">${icon('check')} Mark as returned</button></div>`:''}${!state.user && !item.is_demo && item.status==='open'?`<div class="detail-actions"><button class="btn btn-dark" data-signin-item="${item.id}">Sign in to ${item.type==='found'?'claim this item':'say you found it'}</button></div>`:''}${canClaim?`<div class="detail-claims"><h3>${item.type==='found'?'Is this yours?':'Did you find this?'}</h3><p class="modal-subtitle">Answer the identifying question and leave a message. Your name and email will be shared privately with the person who posted this report.</p><form id="claim-form" data-item-id="${item.id}"><label class="field"><span>${esc(item.verification_question)}</span><input name="answer" required maxlength="100" autocomplete="off" placeholder="Your answer"></label><label class="field"><span>Your message</span><textarea name="message" required minlength="10" maxlength="1000" placeholder="Add a helpful detail or suggest how to arrange the return."></textarea></label><div class="form-error" hidden></div><button class="btn btn-dark" type="submit">${item.type==='found'?'Send claim':'Send found report'} <span>↗</span></button></form></div>`:''}${manage?`<div class="detail-claims"><h3>Private claims <span class="count">${result.claims.length}</span></h3>${result.claims.length?result.claims.map(c=>`<div class="claim-row"><h3>${esc(c.claimant_name)} <span class="status-chip">${esc(c.status)}</span></h3><p>${esc(c.message)}</p><p>${c.answer_verified?'✓ Verification answer matched':''}</p>${c.claimant_email?`<p>Contact: ${esc(c.claimant_email)}</p>`:''}${c.status==='pending' && item.status==='open'?`<button class="btn btn-dark btn-small" data-approve="${c.id}" data-item-id="${item.id}">Confirm return & approve</button>`:''}</div>`).join(''):'<p class="modal-subtitle">No claims yet. Check back here for messages.</p>'}</div>`:''}${result.matches.length?`<div class="detail-claims"><h3>Possible matches</h3><p class="modal-subtitle">Similar category, with nearby dates or a matching location. Please compare the details.</p><div class="match-list">${result.matches.map(m=>`<button class="match-link" data-item="${m.id}"><span>${esc(m.title)}<small>${esc(m.location)} · ${dateLabel(m.event_date)}</small></span><span>↗</span></button>`).join('')}</div></div>`:''}`);
  }catch(error){showModal(`<h2 class="modal-title">We couldn’t open this item.</h2><p class="modal-subtitle">${esc(error.message)}</p>`);}
}
const pageHeading=(eyebrow,title,subtitle)=>`<div class="page-heading"><div><div class="eyebrow">${eyebrow}</div><h1>${title}<span>.</span></h1><p>${subtitle}</p></div><button class="btn btn-dark" data-report="lost">${icon('plus')}Report an item</button></div>`;
async function mine(){
  const page=$('#mine-page');
  page.innerHTML=pageHeading('YOUR CORNER OF THE COMMUNITY','My reports','Keep track of the things you’ve posted and the claims you’ve sent.');
  if(!state.user){page.innerHTML+=empty('Your reports live here.','Sign in to post items, review claims, and track your happy reunions.','<button class="btn btn-dark" data-auth="login">Sign in</button>');return;}
  try{
    const result=await api('mine');
    page.innerHTML+=`<h2 class="section-heading">Your items <span class="count">${result.items.length}</span></h2><div class="items-grid">${result.items.length?result.items.map(card).join(''):empty('Your first report is a click away.','Lost something or made a find? Add it to the board.','<button class="btn btn-light" data-report="lost">Report an item</button>')}</div><h2 class="section-heading">Claims you’ve sent</h2>${result.claims.length?result.claims.map(c=>`<div class="claim-row"><div><h3>${esc(c.title)}</h3><p>${esc(c.message)}</p></div><div class="actions"><span class="status-chip">${esc(c.status)}</span><button class="btn btn-outline btn-small" data-item="${c.item_id}">View item ↗</button></div></div>`).join(''): '<p class="muted">You haven’t sent any claims yet.</p>'}`;
  }catch(error){page.innerHTML+=empty('We couldn’t load your reports.',error.message);}
}
function how(){ $('#how-page').innerHTML=pageHeading('A LITTLE CARE GOES A LONG WAY','Good things find their way back','Three simple steps. A happier community.')+`<div class="info-grid"><article class="info-card"><span class="step-num">01</span><h3>Share the story.</h3><p>Post what you lost or found. Add a description, date, location, and a photo if you have one. Keep one identifying detail private.</p></article><article class="info-card"><span class="step-num">02</span><h3>Make a connection.</h3><p>Search the board, explore possible matches, and answer the verification question. A correct answer lets you send a private claim to the poster.</p></article><article class="info-card"><span class="step-num">03</span><h3>Bring it home.</h3><p>The poster reviews the claim and uses the claimant’s email to arrange a return. After the handover, mark the item as returned and celebrate a little.</p></article></div><div class="info-banner"><strong>A few thoughtful habits</strong><br>Meet in a shared public place. Keep passwords, ID numbers, and private contact details out of public posts. Verification answers help make connections, but always check the item’s details before a handover.</div>`; }
async function admin(){
  const page=$('#admin-page');page.innerHTML=pageHeading('LOOKING AFTER THE COMMUNITY','Admin dashboard','Review listings, check claims, and keep the board helpful.');
  if(state.user?.role!=='admin'){page.innerHTML+=empty('Administrator access needed.','Sign in with the local administrator account to manage this board.','<button class="btn btn-dark" data-auth="login">Sign in</button>');return;}
  try {const r=await api('admin');page.innerHTML+=`<div class="summary-row"><span><strong>${r.counts.users}</strong> accounts</span><span><strong>${r.counts.items}</strong> reports</span><span><strong>${r.counts.claims}</strong> claims</span></div><h2 class="section-heading">All reports</h2>${r.items.map(i=>`<div class="claim-row"><div><h3>${esc(i.title)} ${i.is_demo?'<span class="status-chip">Demo</span>':''}</h3><p>${esc(i.owner_name)} · ${esc(i.location)} · ${esc(i.status)}</p></div><div class="actions"><button class="btn btn-outline btn-small" data-item="${i.id}">Review ↗</button><button class="btn ${i.status==='hidden'?'btn-light':'btn-danger'} btn-small" data-moderate="${i.id}" data-status="${i.status==='hidden'?'open':'hidden'}">${i.status==='hidden'?'Restore':'Hide'}</button></div></div>`).join('')}<h2 class="section-heading">Recent claims</h2>${r.claims.length?r.claims.map(c=>`<div class="claim-row"><div><h3>${esc(c.title)}</h3><p>${esc(c.claimant_name)} · ${esc(c.status)}</p></div><button class="btn btn-outline btn-small" data-item="${c.item_id}">Review ↗</button></div>`).join(''):'<p class="muted">No claims to review yet.</p>'}`;}catch(error){page.innerHTML+=empty('Dashboard unavailable.',error.message);}
}
async function navigate(){
  const key=location.hash.slice(1);state.page=['browse','mine','how','admin'].includes(key)?key:'browse';
  for(const p of ['browse','mine','how','admin']) $('#'+p+'-page').hidden=p!==state.page;
  $$('nav [data-page]').forEach(a=>{a.classList.toggle('active',a.dataset.page===state.page);if(a.dataset.page===state.page)a.setAttribute('aria-current','page');else a.removeAttribute('aria-current');});
  $('#crumb').textContent=({browse:'Browse items',mine:'My reports',how:'How it works',admin:'Admin dashboard'})[state.page];
  if(state.page==='mine') await mine(); else if(state.page==='how') how(); else if(state.page==='admin') await admin(); else await browse();
}
async function refresh(){ const r=await api('bootstrap');state.user=r.user;state.categories=r.categories;$('#category').innerHTML='<option value="">All categories</option>'+r.categories.map(c=>`<option>${esc(c)}</option>`).join('');$('#category').value=state.category;account();updateStats(r.stats);await navigate(); }
async function formAction(form, work){ const submit=$('[type="submit"]',form);const error=$('.form-error',form); error.hidden=true;submit.disabled=true;try{await work();}catch(e){error.textContent=e.message;error.hidden=false;error.scrollIntoView({block:'nearest'});}finally{submit.disabled=false;} }
document.addEventListener('submit',event=>{
  const form=event.target;
  if(!['auth-form','report-form','claim-form'].includes(form.id)) return;
  event.preventDefault();
  formAction(form,async()=>{
    const data=new FormData(form);
    if(form.id==='auth-form') { const r=await api(form.dataset.mode,Object.fromEntries(data));state.user=r.user;account();closeModal();await navigate();toast(form.dataset.mode==='register'?'Welcome to the community!':'Welcome back.');const next=state.afterAuth;state.afterAuth=null;if(next)next(); }
    if(form.id==='report-form') { const photo=data.get('photo');if(photo?.size>5*1024*1024)throw new Error('Choose an image smaller than 5 MB.');const r=await api('create',data);closeModal();await refresh();toast('Your report is on the board.');await itemDetail(r.item.id); }
    if(form.id==='claim-form') {data.set('item_id',form.dataset.itemId);const r=await api('claim',Object.fromEntries(data));closeModal();toast(r.message);location.hash='mine';if(state.page==='mine')await mine();}
  });
});
document.addEventListener('click',async event=>{
  const el=event.target.closest('button');if(!el)return;
  try{
    if(el.hasAttribute('data-close')){state.afterAuth=null;closeModal();}
    else if(el.dataset.auth){auth(el.dataset.auth);}
    else if(el.dataset.report){report(el.dataset.report);}
    else if(el.dataset.item){await itemDetail(el.dataset.item);}
    else if(el.dataset.signinItem){auth('login',()=>itemDetail(el.dataset.signinItem));}
    else if(el.hasAttribute('data-logout')){el.disabled=true;await api('logout',{});state.user=null;state.afterAuth=null;closeModal();await refresh();toast('You’re signed out.');}
    else if(el.dataset.filter){state.filter=el.dataset.filter;$$('[data-filter]').forEach(b=>b.classList.toggle('active',b===el));await browse();}
    else if(el.hasAttribute('data-reset')){state.query='';state.category='';state.filter='all';$('#search').value='';$('#category').value='';$$('[data-filter]').forEach(b=>b.classList.toggle('active',b.dataset.filter==='all'));await browse();}
    else if(el.hasAttribute('data-retry')){await refresh();}
    else if(el.dataset.return){el.disabled=true;const r=await api('return',{item_id:Number(el.dataset.return)});await refresh();await itemDetail(el.dataset.return);toast(r.message);}
    else if(el.dataset.approve){el.disabled=true;const r=await api('approve_claim',{claim_id:Number(el.dataset.approve)});await refresh();await itemDetail(el.dataset.itemId);toast(r.message);}
    else if(el.dataset.moderate){el.disabled=true;const r=await api('moderate',{item_id:Number(el.dataset.moderate),status:el.dataset.status});await refresh();toast(r.message);}
  }catch(error){el.disabled=false;toast(error.message);}
});
let searchTimer;
$('#search').addEventListener('input',e=>{state.query=e.target.value;clearTimeout(searchTimer);searchTimer=setTimeout(browse,220);});
$('#category').addEventListener('change',async e=>{state.category=e.target.value;await browse();});
$('#modal').addEventListener('click',e=>{if(e.target===$('#modal')){state.afterAuth=null;closeModal();}});
$('#modal').addEventListener('cancel',()=>state.afterAuth=null);
window.addEventListener('hashchange',navigate);
async function boot(){hydrateIcons();try{const r=await api('bootstrap');state.user=r.user;state.categories=r.categories;$('#category').innerHTML='<option value="">All categories</option>'+r.categories.map(c=>`<option>${esc(c)}</option>`).join('');account();updateStats(r.stats);await navigate();}catch(error){$('#items-grid').innerHTML=empty('Let’s get connected.',error.message,'<button class="btn btn-light" data-retry>Try again</button>');}}
boot();
