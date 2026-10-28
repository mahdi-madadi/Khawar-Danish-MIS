/* Local interactions only. No student information is stored in browser storage. */
'use strict';
const normalize=value=>String(value).toLocaleLowerCase().replace(/[يى]/g,'ی').replace(/ك/g,'ک').replace(/[۰-۹]/g,d=>String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d))).replace(/[٠-٩]/g,d=>String('٠١٢٣٤٥٦٧٨٩'.indexOf(d))).trim();
const emitChange=element=>element.dispatchEvent(new Event('change',{bubbles:true}));

document.documentElement.classList.add('js');
document.querySelector('[data-menu]')?.addEventListener('click',event=>{
 const open=document.getElementById('sidebar').classList.toggle('menu-open');event.currentTarget.setAttribute('aria-expanded',String(open));
});

document.querySelectorAll('.record-lookup').forEach(box=>{
 const input=box.querySelector('.lookup-query'),hidden=box.querySelector('input[type="hidden"]'),results=box.querySelector('.lookup-results'),status=box.querySelector('.lookup-status'),form=box.closest('form');
 let timer,controller,version=0;
 const close=()=>{clearTimeout(timer);version++;controller?.abort();results.replaceChildren();input.setAttribute('aria-expanded','false');};
 const summary=box.querySelector('.lookup-selection');
 const clearSummary=()=>{if(summary){summary.textContent='';summary.hidden=true;}};
 const clear=()=>{close();input.value='';hidden.value='';input.setCustomValidity('');status.textContent='';clearSummary();emitChange(hidden);};
 const search=async()=>{
  const current=++version;controller?.abort();controller=new AbortController();results.replaceChildren();status.textContent='…';
  try{
   const url=new URL(location.href);url.search='';url.searchParams.set('r','lookup');url.searchParams.set('kind',box.dataset.kind);url.searchParams.set('q',input.value);url.searchParams.set('active',box.dataset.active||'0');
   if(box.dataset.kind==='allocations'){const receipt=form.querySelector('[name="receipt_id"]');if(receipt?.value)url.searchParams.set('receipt_id',receipt.value);}
   const response=await fetch(url,{signal:controller.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error('lookup');
   const data=await response.json();if(current!==version)return;
   status.textContent=data.more?box.dataset.more:(data.rows.length?'':box.dataset.empty);
   input.setAttribute('aria-expanded',String(data.rows.length>0));
   data.rows.forEach(row=>{
    const button=document.createElement('button');button.type='button';button.className='lookup-result';if(row.title){for(const [tag,value] of [['strong',row.title],['span',row.subtitle],['small',row.meta]]){const line=document.createElement(tag);line.textContent=value||'';button.append(line);}}else button.textContent=row.label;
    button.addEventListener('click',()=>{close();input.value=row.label;hidden.value=row.id;input.setCustomValidity('');status.textContent='';if(summary){summary.textContent=row.meta||'';summary.hidden=!row.meta;}input.focus();emitChange(hidden);box.dispatchEvent(new CustomEvent('record-selected',{bubbles:true,detail:row}));});results.append(button);
   });
  }catch(error){if(error.name!=='AbortError'&&current===version){status.textContent=box.dataset.error;input.setAttribute('aria-expanded','false');}}
 };
 input.addEventListener('input',()=>{close();hidden.value='';clearSummary();emitChange(hidden);input.setCustomValidity(input.value?input.placeholder:'');timer=setTimeout(search,250);});
 input.addEventListener('focus',()=>{if(!hidden.value)search();});
 input.addEventListener('keydown',event=>{
  if(event.key==='ArrowDown'){event.preventDefault();results.querySelector('button')?.focus();}
  if(event.key==='Escape'){close();status.textContent='';}
  if(event.key==='Enter'&&!hidden.value&&results.childElementCount){event.preventDefault();results.querySelector('button')?.focus();}
 });
 results.addEventListener('keydown',event=>{
  const buttons=Array.from(results.querySelectorAll('button')),index=buttons.indexOf(document.activeElement);
  if(event.key==='ArrowDown'||event.key==='ArrowUp'){event.preventDefault();buttons[(index+(event.key==='ArrowDown'?1:-1)+buttons.length)%buttons.length]?.focus();}
  if(event.key==='Escape'){input.focus();close();status.textContent='';}
 });
 box.querySelector('[data-clear-lookup]')?.addEventListener('click',()=>{clear();input.focus();});
 document.addEventListener('click',event=>{if(!box.contains(event.target))close();});
 form.addEventListener('submit',event=>{if((input.required||input.value)&&!hidden.value){event.preventDefault();input.setCustomValidity(input.placeholder);input.reportValidity();}});
 if(box.dataset.kind==='allocations'){
  const receipt=form.querySelector('[name="receipt_id"]');receipt?.addEventListener('change',clear);
 }
});

document.querySelectorAll('.select-search').forEach(input=>input.addEventListener('input',()=>{
 const select=input.nextElementSibling,term=normalize(input.value);
 Array.from(select.options).forEach(option=>option.hidden=!!option.value&&!normalize(option.text).includes(term));
}));

document.querySelectorAll('[data-when]').forEach(group=>{
 const [name,value]=group.dataset.when.split(':'),control=group.closest('form').querySelector(`[name="${name}"]`);
 const update=()=>{const visible=control.value===value;group.hidden=!visible;group.querySelectorAll('input,select,textarea').forEach(input=>input.disabled=!visible);};
 control.addEventListener('change',update);update();
});

document.querySelectorAll('select[name^="mark_status["]').forEach(select=>{
 const input=select.closest('tr').querySelector('input[name^="marks["]');
 const update=()=>{input.disabled=select.value!=='graded';input.required=!input.disabled;};
 select.addEventListener('change',update);update();
});

document.querySelectorAll('[data-roster-search]').forEach(input=>{
 const panel=input.closest('.panel'),rows=Array.from(panel.querySelectorAll('.roster-table tbody tr')),count=panel.querySelector('[data-roster-count]');
 const update=()=>{const term=normalize(input.value);let visible=0;rows.forEach(row=>{row.hidden=!normalize(row.textContent).includes(term);if(!row.hidden)visible++;});count.textContent=`${visible} / ${rows.length}`;};
 input.addEventListener('input',update);update();
});

document.querySelectorAll('[data-bulk]').forEach(button=>button.addEventListener('click',()=>{
 const panel=button.closest('.panel'),overwrite=panel.querySelector('[data-overwrite]')?.checked;
 const selects=Array.from(panel.querySelectorAll('select.attendance-status')).filter(select=>!select.closest('tr').hidden&&(overwrite||!select.value));
 panel.bulkUndo=selects.map(select=>[select,select.value]);selects.forEach(select=>{select.value=button.dataset.bulk;emitChange(select);});
 const undo=panel.querySelector('[data-undo-bulk]');if(undo)undo.disabled=!selects.length;
}));
document.querySelectorAll('[data-undo-bulk]').forEach(button=>button.addEventListener('click',()=>{
 const panel=button.closest('.panel');(panel.bulkUndo||[]).forEach(([select,value])=>{select.value=value;emitChange(select);});panel.bulkUndo=[];button.disabled=true;
}));

// Expand optional sections when native validation finds a field inside them.
document.addEventListener('invalid',event=>{
 for(let parent=event.target.parentElement;parent;parent=parent.parentElement)if(parent.tagName==='DETAILS')parent.open=true;
},true);

document.querySelectorAll('form[method="post"]').forEach(form=>form.addEventListener('submit',event=>{
 if(event.defaultPrevented)return;
 if(form.dataset.confirm&&!confirm(form.dataset.confirm)){event.preventDefault();return;}
 if(form.dataset.submitting==='1'){event.preventDefault();return;}
 form.dataset.submitting='1';
 // Defer locking until the browser has captured the clicked next_step button.
 setTimeout(()=>{form.querySelectorAll('button[type="submit"]').forEach(button=>{button.disabled=true;button.dataset.submitLock='1';});const progress=form.querySelector('.form-progress');if(progress)progress.textContent=form.dataset.saving||'…';},0);
}));
window.addEventListener('pageshow',()=>{
 document.querySelectorAll('[data-submit-lock]').forEach(button=>{button.disabled=false;delete button.dataset.submitLock;});
 document.querySelectorAll('[data-submitting]').forEach(form=>{delete form.dataset.submitting;const progress=form.querySelector('.form-progress');if(progress)progress.textContent='';});
});

document.querySelectorAll('[data-balance-preview]').forEach(preview=>{
 const selected=preview.closest('form').querySelector('[name="student_id"]');let version=0,controller;
 const update=async()=>{
  const current=++version;controller?.abort();preview.textContent='';if(!selected.value)return;controller=new AbortController();preview.textContent='…';
  try{const url=new URL(location.href);url.search='';url.searchParams.set('r','student_balance');url.searchParams.set('student_id',selected.value);
   const response=await fetch(url,{signal:controller.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error('balance');const data=await response.json();if(current!==version)return;
   preview.textContent=`${preview.dataset.balanceLabel}: ${data.receivables} AFN · ${preview.dataset.advanceLabel}: ${data.advance} AFN`;
  }catch(error){if(error.name!=='AbortError'&&current===version)preview.textContent=preview.dataset.error;}
 };
 selected.addEventListener('change',update);update();
});

const failed=document.querySelector('[data-form-error]');
if(failed){const form=document.querySelector(`form[data-action="${document.body.dataset.failedAction||''}"]`);form?.closest('details')?.setAttribute('open','');failed.focus();}
const focus=new URL(location.href).searchParams.get('focus');
if(focus&&!failed){const target=document.querySelector(`form[data-action="${CSS.escape(focus)}"]`)||document.getElementById('panel-'+focus);if(target){for(let parent=target;parent;parent=parent.parentElement)if(parent.tagName==='DETAILS')parent.open=true;target.scrollIntoView({block:'start'});}}

// Tables scroll independently on narrow screens, preserving readable columns.
document.querySelectorAll('.table-wrap').forEach(wrap=>{
 wrap.tabIndex=0;wrap.setAttribute('aria-label',document.body.dataset.tableHint||'');
 const hint=document.createElement('small');hint.className='table-scroll-hint no-print';hint.textContent=document.body.dataset.tableHint||'';wrap.before(hint);
 const update=()=>hint.hidden=wrap.scrollWidth<=wrap.clientWidth+1;new ResizeObserver(update).observe(wrap);update();
});
document.querySelectorAll('[data-paper-filters]').forEach(form=>{
  ['class_subject_id','month'].forEach(name=>form.elements[name]?.addEventListener('change',()=>{form.elements.time_slot.value='';}));
});

document.querySelectorAll('form[data-action="teaching_group"]').forEach(form=>{
 const course=form.elements.program_id,subject=form.elements.subject_id;
 const filter=()=>{for(const opt of subject.options){if(!opt.value)continue;opt.hidden=opt.dataset.program!==course.value;opt.disabled=opt.hidden;}if(subject.selectedOptions[0]?.disabled)subject.value='';};
 course.addEventListener('change',filter);filter();
});

document.querySelectorAll('form[data-action="certificate"]').forEach(form=>{
 const templates=form.querySelector('[data-certificate-templates]'),kind=form.elements.kind,text=form.elements.wording;
 if(!templates||!kind||!text)return;
 kind.addEventListener('change',()=>{if(!text.value.trim()||Object.values(templates.dataset).includes(text.value))text.value=templates.dataset[kind.value]||'';});
});

// Shared controls for every displayed data table. Server-paginated module lists
// use their global search and sort links; other tables filter their loaded rows.
(() => {
 const fa=document.documentElement.lang==='fa';
 const norm=v=>v.replace(/[۰-۹]/g,c=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(c)).replace(/[٠-٩]/g,c=>'٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/ي/g,'ی').replace(/ك/g,'ک').toLocaleLowerCase().trim();
 const collator=new Intl.Collator(fa?'fa':'en',{numeric:true,sensitivity:'base'});
 document.querySelectorAll('.table-wrap > table').forEach((table,index)=>{
  if(table.closest('.print-document,.official-sheet')||table.hasAttribute('data-server-sort'))return;
  const body=table.tBodies[0];if(!body)return;
  const rows=Array.from(body.rows).filter(r=>!r.querySelector('.empty'));if(!rows.length)return;
  const tools=document.createElement('div');tools.className='table-tools no-print';
  const label=document.createElement('label');label.textContent=fa?'جستجو در این جدول':'Search this table';
  const input=document.createElement('input');input.type='search';input.id='table-search-'+index;label.htmlFor=input.id;label.append(input);
  const count=document.createElement('span');count.className='table-match-count';count.setAttribute('role','status');tools.append(label,count);table.parentElement.before(tools);
  const empty=document.createElement('p');empty.className='table-filter-empty';empty.textContent=fa?'نتیجه‌ای یافت نشد':'No matching records';empty.hidden=true;table.parentElement.after(empty);
  const filter=()=>{const tokens=norm(input.value).split(/\s+/).filter(Boolean);let shown=0;rows.forEach(row=>{const text=norm(Array.from(row.cells).map(cell=>{const select=cell.querySelector('select');return select?select.selectedOptions[0]?.textContent||'':cell.textContent;}).join(' '));row.hidden=!tokens.every(t=>text.includes(t));if(!row.hidden)shown++;});count.textContent=shown+' / '+rows.length;empty.hidden=shown!==0;};input.addEventListener('input',filter);filter();
  Array.from(table.tHead?.rows[0]?.cells||[]).forEach((th,col)=>{
   if(th.classList.contains('no-print')||th.querySelector('a,button,input'))return;
   const button=document.createElement('button');button.type='button';button.className='table-sort';const text=th.textContent;button.textContent=text+' ↕';button.title=fa?'مرتب‌سازی':'Sort';th.textContent='';th.append(button);let asc=false;
   button.addEventListener('click',()=>{asc=!asc;Array.from(table.tHead.rows[0].cells).forEach(h=>h.removeAttribute('aria-sort'));th.setAttribute('aria-sort',asc?'ascending':'descending');button.textContent=text+(asc?' ↑':' ↓');const value=row=>{const cell=row.cells[col];return norm(cell?.querySelector('input,select')?.value||cell?.textContent||'');};rows.sort((a,b)=>(asc?1:-1)*collator.compare(value(a),value(b))).forEach(row=>body.append(row));});
  });
 });
})();

// Show a selected portrait in the editor before it is saved.
document.querySelectorAll('.photo-editor').forEach(editor=>{
 const input=editor.querySelector('input[type=file]'),image=editor.querySelector('[data-photo-preview]'),remove=editor.querySelector('[name=remove_photo]');if(!input||!image)return;
 const saved={src:image.getAttribute('src'),alt:image.alt,placeholder:image.classList.contains('avatar-placeholder')};let version=0;
 const restore=()=>{image.src=saved.src;image.alt=saved.alt;image.classList.toggle('avatar-placeholder',saved.placeholder);};
 input.addEventListener('change',()=>{const current=++version,file=input.files[0];if(!file){restore();return;}if(!['image/jpeg','image/png'].includes(file.type)||file.size>2*1024*1024){input.value='';restore();return;}const reader=new FileReader();reader.addEventListener('load',()=>{if(version!==current)return;image.src=reader.result;image.classList.remove('avatar-placeholder');image.alt=document.documentElement.lang==='fa'?'پیش‌نمایش عکس انتخاب‌شده':'Selected photo preview';if(remove)remove.checked=false;});reader.readAsDataURL(file);});
 remove?.addEventListener('change',()=>{version++;if(remove.checked){input.value='';image.src='assets/person-placeholder.svg?v=1';image.classList.add('avatar-placeholder');image.alt=document.documentElement.lang==='fa'?'عکس شخصی ثبت نشده است':'No profile photo';}else restore();});
});
