/* Print a chosen document or section, preserving the screen's current state. */
'use strict';
(() => {
 const fa=document.documentElement.lang==='fa',main=document.querySelector('main');if(!main)return;
 const documentSelector='.legacy-print,.paper-attendance,.result-card,.id-card,.issued-certificate,.employee-payslip';
 let output=null;
 const standalone=()=>Array.from(main.querySelectorAll(documentSelector)).filter(el=>!el.parentElement.closest(documentSelector));
 const sections=()=>Array.from(main.querySelectorAll('.panel')).filter(el=>!el.classList.contains('no-print')&&(el.querySelector('table,.student-heading,.profile-facts')||el.querySelector('.signature')));
 function prepare(targets){
  output?.remove();output=document.createElement('div');output.id='print-output';output.dir=document.documentElement.dir;
  if(!targets.some(el=>el.matches(documentSelector))){const header=main.querySelector('.print-header')?.cloneNode(true);if(header){header.querySelector('h2')?.remove();header.querySelectorAll('p').forEach(p=>{if(!p.textContent.replace(/[·\s]/g,''))p.remove();});output.append(header);}}
  for(const target of targets){const clone=target.cloneNode(true);
   clone.querySelectorAll('.table-sort').forEach(el=>{const span=document.createElement('span');span.textContent=el.textContent.replace(/[↕↑↓]/g,'').trim();el.replaceWith(span);});
   clone.querySelectorAll('select').forEach(el=>{const span=document.createElement('span');span.textContent=el.selectedOptions[0]?.textContent||'';el.replaceWith(span);});
   clone.querySelectorAll('input:not([type=hidden]),textarea').forEach(el=>{const span=document.createElement('span');span.textContent=el.type==='checkbox'?(el.checked?'✓':''):el.value;el.replaceWith(span);});
   clone.querySelectorAll('.no-print,.table-tools,.table-filter-empty,.table-scroll-hint,.roster-tools,.actions,.workflow,.notice,button,input[type=hidden],.lookup-results,.lookup-status,.form-actions,.section-print,a.button').forEach(el=>el.remove());
   clone.querySelectorAll('form').forEach(form=>{if(form.querySelector('table'))form.replaceWith(...form.childNodes);else form.remove();});
   clone.querySelectorAll('details').forEach(el=>el.open=true);
   clone.querySelectorAll('[id]').forEach(el=>el.removeAttribute('id'));clone.removeAttribute('id');
   output.append(clone);
  }
  document.body.append(output);document.body.classList.add('print-focused');
 }
 function print(targets){prepare(targets);window.print();}
 function choose(){const docs=standalone();if(docs.length){print(docs);return;}const list=sections();if(!list.length)return;if(list.length===1){print(list);return;}
  const dialog=document.createElement('dialog');dialog.className='print-picker no-print';const title=document.createElement('h3');title.textContent=fa?'انتخاب بخش برای چاپ':'Choose a section to print';dialog.append(title);
  for(const section of list){const button=document.createElement('button');button.type='button';button.textContent=section.querySelector('h2,summary')?.textContent.trim()||document.title;button.addEventListener('click',()=>{dialog.close();dialog.remove();print([section]);});dialog.append(button);}
  const close=document.createElement('button');close.type='button';close.className='secondary';close.textContent=fa?'بستن':'Close';close.addEventListener('click',()=>{dialog.close();dialog.remove();});dialog.append(close);dialog.addEventListener('cancel',()=>dialog.remove());document.body.append(dialog);dialog.showModal();
 }
 document.querySelectorAll('[data-print]').forEach(button=>button.addEventListener('click',choose));
 if(main.querySelector('[data-print]')&&!standalone().length)for(const section of sections()){
  const button=document.createElement('button');button.type='button';button.className='secondary section-print no-print';button.textContent=fa?'چاپ این بخش':'Print this section';button.addEventListener('click',()=>print([section]));section.prepend(button);
 }
 window.addEventListener('beforeprint',()=>{if(!output){const docs=standalone();prepare(docs.length?docs:sections().slice(0,1));}});
 window.addEventListener('afterprint',()=>{output?.remove();output=null;document.body.classList.remove('print-focused');});
 document.addEventListener('keydown',event=>{if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='p'){event.preventDefault();choose();}});
})();
