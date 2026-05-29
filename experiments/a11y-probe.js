'use strict';
const fs=require('fs'),path=require('path'),{spawn}=require('child_process'),{chromium}=require('playwright');
const axeSource=fs.readFileSync(require.resolve('axe-core/axe.min.js'),'utf8');
const root=process.cwd();const host='127.0.0.1',port='8896',baseURL=`http://${host}:${port}`;
const server=spawn('php',['-S',`${host}:${port}`,'dev/php/router.php'],{cwd:root,env:{...process.env,TONBANKCARD_PROFILE:'local',TONBANKCARD_BASE_URL:`${baseURL}/`,TONBANKCARD_LOCAL_BASE_URL:`${baseURL}/`,TONBANKCARD_CDN:'false'},stdio:['ignore','ignore','ignore']});
(async()=>{
  for(let i=0;i<60;i++){try{const r=await fetch(baseURL+'/');if(r.ok)break;}catch(e){}await new Promise(r=>setTimeout(r,250));}
  const b=await chromium.launch();const p=await b.newPage();
  await p.goto(baseURL+'/',{waitUntil:'domcontentloaded'});await p.waitForTimeout(1500);
  await p.addScriptTag({content:axeSource});
  const res=await p.evaluate(async()=>await window.axe.run(document,{resultTypes:['violations'],runOnly:{type:'rule',values:['color-contrast','link-in-text-block']}}));
  for(const v of res.violations){console.log('### '+v.id);
    for(const n of v.nodes.slice(0,8)){const d=(n.any[0]||{}).data||{};console.log(JSON.stringify({target:n.target,fg:d.fgColor,bg:d.bgColor,ratio:d.contrastRatio,exp:d.expectedContrastRatio,fontSize:d.fontSize,fontWeight:d.fontWeight}));}
  }
  await b.close();server.kill('SIGTERM');
})();
