/* Shared UI interactions. This file does NOT send credentials, query real results, or save data. */
(() => {
 'use strict';
 const $ = (q, root = document) => root.querySelector(q);
 const $$ = (q, root = document) => Array.from(root.querySelectorAll(q));
 let toastTimer;
 function toast(msg){
   const el = $('#toast'); if(!el)return;
   el.textContent=msg; el.classList.add('show');
   clearTimeout(toastTimer); toastTimer=setTimeout(()=>el.classList.remove('show'),4300);
 }
 $$('[data-public-menu]').forEach(btn=>btn.addEventListener('click',()=>{
   const nav=$('#public-nav');if(!nav)return;
   nav.classList.toggle('open');btn.setAttribute('aria-expanded',String(nav.classList.contains('open')));
 }));
 $$('#public-nav a').forEach(link=>link.addEventListener('click',()=>$('#public-nav')?.classList.remove('open')));
 $('[data-menu-toggle]')?.addEventListener('click',()=>{$('#sidebar')?.classList.add('open');$('.dash-scrim')?.classList.add('show');});
 $('[data-menu-close]')?.addEventListener('click',()=>{$('#sidebar')?.classList.remove('open');$('.dash-scrim')?.classList.remove('show');});
 // Login student/admin tabs
 $$('[data-login-tab]').forEach(tab=>tab.addEventListener('click',()=>{
   const which=tab.dataset.loginTab;
   $$('[data-login-tab]').forEach(t=>{const active=t===tab;t.classList.toggle('active',active);t.setAttribute('aria-selected',String(active));});
   ['student','admin'].forEach(t=>{const el=$('#'+t+'-panel');if(el)el.hidden=t!==which;});
 }));
 $$('[data-toggle-password]').forEach(btn=>btn.addEventListener('click',()=>{
   const input=document.getElementById(btn.dataset.togglePassword);
   if(input){input.type=input.type==='password'?'text':'password';btn.setAttribute('aria-label',input.type==='password'?'Hiện mật khẩu':'Ẩn mật khẩu');}
 }));
 // UI-only forms never submit or modify database.
 $$('[data-demo-form]').forEach(form=>form.addEventListener('submit',ev=>{
   ev.preventDefault(); if(!form.reportValidity())return;
   const context=form.dataset.demoContext||'Thao tác';
   toast(`${context}: Đây là giao diện minh họa, chưa xử lý hoặc lưu dữ liệu. Không nhập thông tin thật.`);
 }));
 $$('[data-demo-action]').forEach(btn=>btn.addEventListener('click',()=>toast('Chức năng này sẽ được kết nối khi hoàn thiện module.')));
 // Frontend table filtering
 $('[data-table-search]')?.addEventListener('input',ev=>{
   const query=ev.target.value.trim().toLocaleLowerCase('vi');
   $$('[data-search-row]').forEach(row=>{row.hidden=!row.textContent.toLocaleLowerCase('vi').includes(query);});
 });
 // School list filtering
 let currentRegion='all';
 function filterSchools(){
   const q=($('[data-school-search]')?.value||'').trim().toLocaleLowerCase('vi');let visible=0;
   $$('[data-school-item]').forEach(item=>{
     const matchesRegion=currentRegion==='all'||item.dataset.region===currentRegion;
     const matchesQuery=item.textContent.toLocaleLowerCase('vi').includes(q);
     item.hidden=!(matchesRegion&&matchesQuery); if(matchesRegion&&matchesQuery)visible++;
   }); const empty=$('#school-empty'); if(empty)empty.hidden=visible!==0;
 }
 $$('[data-school-filter]').forEach(btn=>btn.addEventListener('click',()=>{
   currentRegion=btn.dataset.schoolFilter;
   $$('[data-school-filter]').forEach(el=>{const active=el===btn;el.classList.toggle('active',active);el.setAttribute('aria-pressed',String(active));});
   filterSchools();
 }));
 $('[data-school-search]')?.addEventListener('input',filterSchools);
 // Client-side fake result for DEMO001 ONLY. No API or personal data.
 $('#lookup-demo-form')?.addEventListener('submit',ev=>{
   ev.preventDefault();const form=ev.currentTarget;if(!form.reportValidity())return;
   const id=($('#lookup-id')?.value||'').trim().toUpperCase();const box=$('#lookup-result');
   if(id==='DEMO001') {if(box){box.hidden=false;box.scrollIntoView({behavior:'smooth',block:'center'});}toast('Đã hiển thị kết quả mẫu DEMO001. Đây không phải kết quả thật.');}
   else{if(box)box.hidden=true;toast('Bộ giao diện chỉ có mã mẫu DEMO001. Không có dữ liệu thí sinh thật để tra cứu.');}
 });
})();
