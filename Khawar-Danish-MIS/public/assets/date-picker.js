/* Calendar controls preserve the server's selected calendar and date format. */
'use strict';
(() => {
 const fa=document.documentElement.lang==='fa';
 const div=(a,b)=>Math.floor(a/b),pad=n=>String(n).padStart(2,'0');
 function j2g(jy,jm,jd){jy+=1595;let days=-355668+365*jy+div(jy,33)*8+div(jy%33+3,4)+jd+(jm<7?(jm-1)*31:(jm-7)*30+186),gy=400*div(days,146097);days%=146097;if(days>36524){gy+=100*div(--days,36524);days%=36524;if(days>=365)days++;}gy+=4*div(days,1461);days%=1461;if(days>365){gy+=div(days-1,365);days=(days-1)%365;}let gd=days+1,gm=1;const months=[0,31,gy%4===0&&(gy%100!==0||gy%400===0)?29:28,31,30,31,30,31,31,30,31,30,31];for(;gm<=12&&gd>months[gm];gm++)gd-=months[gm];return [gy,gm,gd];}
 const date=(y,m,d)=>`${y}-${pad(m)}-${pad(d)}`;
 const monthNames=fa?['حمل','ثور','جوزا','سرطان','اسد','سنبله','میزان','عقرب','قوس','جدی','دلو','حوت']:['Hamal','Sawr','Jawza','Saratan','Asad','Sonbola','Mizan','Aqrab','Qaws','Jadi','Dalw','Hoot'];
 document.querySelectorAll('[data-date-picker]').forEach(input=>{
  if(input.dataset.calendar==='g'){
   input.addEventListener('click',()=>{try{input.showPicker?.();}catch{}});return;
  }
  const monthOnly=input.dataset.mode==='month',dialog=document.createElement('dialog');dialog.className='calendar-picker no-print';dialog.dir=fa?'rtl':'ltr';dialog.setAttribute('aria-label',fa?'انتخاب تاریخ هجری شمسی':'Select Solar Hijri date');document.body.append(dialog);
  const heading=document.createElement('h3');heading.textContent=fa?(monthOnly?'انتخاب ماه':'انتخاب تاریخ'):(monthOnly?'Select month':'Select date');dialog.append(heading);
  const nav=document.createElement('div');nav.className='picker-nav';
  const button=(text,action)=>{const b=document.createElement('button');b.type='button';b.textContent=text;b.addEventListener('click',action);return b;};
  const year=document.createElement('select'),month=document.createElement('select');year.setAttribute('aria-label',fa?'سال':'Year');month.setAttribute('aria-label',fa?'ماه':'Month');
  for(let y=1300;y<=1499;y++){const o=new Option(String(y),String(y));year.add(o);}monthNames.forEach((name,i)=>month.add(new Option(name,String(i+1))));
  const prev=button('‹',()=>move(-1)),next=button('›',()=>move(1));prev.setAttribute('aria-label',fa?'ماه قبل':'Previous month');next.setAttribute('aria-label',fa?'ماه بعد':'Next month');nav.append(prev,month,year,next);dialog.append(nav);
  const grid=document.createElement('div');grid.className='picker-days';dialog.append(grid);
  const footer=document.createElement('div');footer.className='picker-actions';
  const finish=value=>{input.value=value;input.setCustomValidity('');input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}));dialog.close();input.focus();};
  footer.append(button(fa?'امروز':'Today',()=>finish(monthOnly?input.dataset.today.slice(0,7):input.dataset.today)));
  if(!input.required)footer.append(button(fa?'پاک کردن':'Clear',()=>finish('')));
  if(monthOnly)footer.append(button(fa?'انتخاب ماه':'Choose month',()=>finish(`${year.value}-${pad(month.value)}`)));
  footer.append(button(fa?'بستن':'Close',()=>dialog.close()));dialog.append(footer);
  function move(delta){let y=Number(year.value),m=Number(month.value)+delta;if(m<1){m=12;y--;}if(m>12){m=1;y++;}if(y<1300||y>1499)return;year.value=y;month.value=m;render();}
  function render(){grid.replaceChildren();if(monthOnly){grid.hidden=true;return;}const y=Number(year.value),m=Number(month.value),start=j2g(y,m,1),following=j2g(m===12?y+1:y,m===12?1:m+1,1),count=Math.round((Date.UTC(...[following[0],following[1]-1,following[2]])-Date.UTC(start[0],start[1]-1,start[2]))/86400000),offset=(new Date(Date.UTC(start[0],start[1]-1,start[2])).getUTCDay()+1)%7;
   const weekdays=fa?['ش','ی','د','س','چ','پ','ج']:['Sa','Su','Mo','Tu','We','Th','Fr'];weekdays.forEach(text=>{const span=document.createElement('strong');span.textContent=text;grid.append(span);});for(let i=0;i<offset;i++)grid.append(document.createElement('span'));
   for(let d=1;d<=count;d++){const value=date(y,m,d),b=button(String(d),()=>finish(value));b.dataset.date=value;b.setAttribute('aria-label',value);b.className='secondary';if(value===input.value){b.classList.add('selected');b.setAttribute('aria-pressed','true');}if(value===input.dataset.today)b.setAttribute('aria-current','date');grid.append(b);}
  }
  year.addEventListener('change',render);month.addEventListener('change',render);
  function open(){if(dialog.open)return;const parsed=(/^1[34]\d{2}-(0[1-9]|1[0-2])/.test(input.value)?input.value:input.dataset.today).split('-');year.value=parsed[0];month.value=Number(parsed[1]);render();dialog.showModal();(grid.querySelector('.selected')||month).focus();}
  input.addEventListener('click',open);input.addEventListener('keydown',e=>{if(['Enter',' ','ArrowDown'].includes(e.key)){e.preventDefault();open();}});
  dialog.addEventListener('click',e=>{if(e.target===dialog){const rect=dialog.getBoundingClientRect();if(e.clientX<rect.left||e.clientX>rect.right||e.clientY<rect.top||e.clientY>rect.bottom)dialog.close();}});
 });
})();
