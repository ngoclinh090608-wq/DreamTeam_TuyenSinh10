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
 const publicButton = $('[data-public-menu]');
 const publicNav = $('#public-nav');
 function closePublicMenu(){
   publicNav?.classList.remove('open');
   publicButton?.setAttribute('aria-expanded','false');
 }
 publicButton?.addEventListener('click',()=>{
   const open=publicNav?.classList.toggle('open');
   publicButton.setAttribute('aria-expanded',String(Boolean(open)));
 });
 $$('#public-nav a').forEach(link=>link.addEventListener('click',closePublicMenu));
 const sidebar=$('#sidebar');
 const sidebarButton=$('[data-menu-toggle]');
 const scrim=$('[data-menu-close]');
 function closeSidebar(){
   sidebar?.classList.remove('open'); scrim?.classList.remove('show');
   sidebarButton?.setAttribute('aria-expanded','false');
 }
 sidebarButton?.addEventListener('click',()=>{
   sidebar?.classList.add('open'); scrim?.classList.add('show');
   sidebarButton.setAttribute('aria-expanded','true');
   sidebar?.querySelector('a')?.focus();
 });
 scrim?.addEventListener('click',closeSidebar);
 const support=$('.support-menu');
 support?.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>support.open=false));
 document.addEventListener('click',ev=>{ if(support && !support.contains(ev.target))support.open=false; });
 document.addEventListener('keydown',ev=>{
   if(ev.key==='Escape'){
     if(support?.open){ support.open=false; support.querySelector('summary')?.focus(); }
     if(publicNav?.classList.contains('open')){ closePublicMenu(); publicButton?.focus(); }
     if(sidebar?.classList.contains('open')){ closeSidebar(); sidebarButton?.focus(); }
   }
 });
 const desktop=window.matchMedia('(min-width: 1051px)');
 desktop.addEventListener('change',()=>{closePublicMenu();closeSidebar();});
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
   const count=$('[data-school-count]'); if(count)count.textContent=`${visible} trường`;
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
   if(id==='DEMO001') {if(box){box.hidden=false;box.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'center'});}toast('Đã hiển thị kết quả mẫu DEMO001. Đây không phải kết quả thật.');}
   else{if(box)box.hidden=true;toast('Bộ giao diện chỉ có mã mẫu DEMO001. Không có dữ liệu thí sinh thật để tra cứu.');}
 });
 if(new URLSearchParams(window.location.search).has('lookup_id')){
   const form=$('#lookup-demo-form'); if(form?.checkValidity())form.requestSubmit();
 }
})();
