'use strict';
const {spawn}=require('child_process'),{chromium}=require('playwright');
const host='127.0.0.1',port='8899',baseURL=`http://${host}:${port}`;
const server=spawn('php',['-S',`${host}:${port}`,'dev/php/router.php'],{cwd:process.cwd(),env:{...process.env,TONBANKCARD_PROFILE:'local',TONBANKCARD_BASE_URL:`${baseURL}/`,TONBANKCARD_LOCAL_BASE_URL:`${baseURL}/`,TONBANKCARD_CDN:'false'},stdio:['ignore','ignore','ignore']});
(async()=>{
  for(let i=0;i<60;i++){try{const r=await fetch(baseURL+'/');if(r.ok)break;}catch(e){}await new Promise(r=>setTimeout(r,250));}
  const b=await chromium.launch();const p=await b.newPage({viewport:{width:1280,height:900}});
  await p.goto(baseURL+'/',{waitUntil:'domcontentloaded'});await p.waitForTimeout(1500);
  const info=await p.evaluate(()=>{
    const inp=document.querySelectorAll('input');
    let found=null;
    inp.forEach(el=>{const cs=getComputedStyle(el);if(cs.backgroundColor==='rgb(27, 178, 218)'){found={id:el.id,type:el.type,parent:el.closest('[class]')?el.closest('[class]').className:'',outer:el.outerHTML.slice(0,160)};}});
    // count primary filled buttons
    const pbtn=document.querySelectorAll('.v-btn.primary.v-btn--has-bg').length;
    const pchip=document.querySelectorAll('.v-chip.primary').length;
    return {found,pbtn,pchip};
  });
  console.log(JSON.stringify(info,null,2));
  await b.close();server.kill('SIGTERM');
})();
