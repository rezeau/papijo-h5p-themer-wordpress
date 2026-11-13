'use strict';
const assert = require('node:assert/strict');
const api = require('../../assets/js/theme-controller.js');
const { fixture, flush, envelope, P } = require('./theme-controller.js');
let checks = 0;
function eq(a,b,text) { assert.deepEqual(a,b,text); checks++; }
const div = {content_id:'10',embed:'div',library:'H5P.Accordion 1.0'};
const frame = {content_id:'12',embed:'iframe',library:'H5P.Column 1.2'};
function setup(players=[div,frame], options={}) {
  const f=fixture(players,{noStart:true});
  f.win.location=new URL('https://fixture.test/wp-admin/admin.php?page=h5p&task=show&id=10');
  f.doc.URL=f.win.location.href;
  f.manifest.admin_viewer={content_id:'10',nonce:'123456abcd'};
  f.script=f.doc.body.append(f.el('script','',{'data-pjh5pwp-bootstrap':'1','data-pjh5pwp-admin-viewer':'10','data-pjh5pwp-admin-nonce':'123456abcd'}));
  f.win.requestAnimationFrame=fn=>f.win.setTimeout(fn,16);
  f.win.cancelAnimationFrame=id=>f.win.clearTimeout(id);
  if(options.before) options.before(f);
  return f;
}
async function run() {
  for(const density of ['original','small','medium','large']) {
    const f=setup(), c=api.create(f.win,f.manifest);
    eq(typeof c.start,'function','Exact official admin context admitted'); c.start(); await flush();
    eq(f.requests.length,1,'One same-origin appearance request serves distinct players');
    const r=f.requests[0], fields=new URLSearchParams(r.init.body);
    eq([...fields.keys()],['action','request_id','admin_content_id','admin_nonce','admin_players'],'Separate exact admin schema');
    eq(fields.get('admin_content_id'),'10','Route identity passed with grant');
    eq(fields.get('admin_nonce'),'123456abcd','Server-issued nonce forwarded');
    eq(fields.get('admin_players'),'10,12','Every distinct player must be authorized server-side');
    const body=envelope(r.id); body.appearance.density={selection:density,preserve_native:density==='original'};
    r.respond(body);await flush();
    for(const item of f.items) {
      eq(item.root.style.getPropertyValue(P),'#123','Existing Theme application in DIV/internal iframe');
      eq(item.root.classList.contains('h5p-'+(density==='original'?'large':density)),true,'Existing independent native Density application');
      eq(item.instance.resizeCalls.length>0,density==='small'||density==='medium','Density changes resize; matching native state remains a no-op');
    }
    c.stop(); f.items.forEach(item=>{eq(item.root.style.getPropertyValue(P),'','Existing color cleanup');eq(item.root.classList.contains('h5p-large'),true,'Original native Density restored');});
  }
  const badSetups=[
    f=>delete f.manifest.admin_viewer,
    f=>f.script.remove(),
    f=>f.script.setAttribute('data-pjh5pwp-admin-nonce','0000000000'),
    f=>f.manifest.admin_viewer.nonce='forged',
    f=>f.manifest.admin_viewer.content_id='12',
    f=>f.doc.body.append(f.el('script','',{'data-pjh5pwp-bootstrap':'1','data-pjh5pwp-admin-viewer':'10','data-pjh5pwp-admin-nonce':'123456abcd'})),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/profile.php'),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/admin.php?page=h5p'),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/admin.php?page=h5p&task=results&id=10'),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/admin-ajax.php?action=h5p_embed&id=10'),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/admin.php?page=h5p&task=show&id=10&id=12'),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/admin.php?page=h5p&task=show&id=10&extra=1'),
    f=>f.win.location=new URL('https://fixture.test/wp-admin/admin.php?%70age=h5p&task=show&id=10'),
    f=>f.win.H5PEditor={},
    f=>f.win.top={},
    f=>f.manifest.players.push({...div})
  ];
  for(const before of badSetups) {
    const f=setup(undefined,{before});eq(api.create(f.win,f.manifest),null,'Unqualified/forged/duplicate administration context rejected');
    f.doc.readyState='loading';
    const boot=api.bootstrap(f.win);
    // A valid head context can arm the existing watchdog; invalid footer metadata must still remain inert.
    if(boot) {
      f.doc.body.append(f.el('span','',{'data-pjh5pwp-manifest':JSON.stringify(f.manifest)}));
      f.doc.readyState='interactive';f.doc.emit('DOMContentLoaded');await flush();
      eq(f.requests.length,0,'Invalid footer/context never requests appearance');boot.stop();
    } else { eq(f.requests.length,0,'Rejected bootstrap remains inert'); }
  }
  const f=setup();f.doc.readyState='loading';
  f.doc.body.append(f.el('span','',{'data-pjh5pwp-manifest':JSON.stringify(f.manifest)}));
  for(const item of f.items) {
    const boundary=item.wrapper||item.root;boundary.setAttribute('data-pjh5pwp-pending',item.p.content_id);
    if(item.frame) { item.frame.style.height='200px';item.doc.body.scrollHeight=200; }
  }
  const boot=api.bootstrap(f.win);eq(typeof boot.refresh,'function','Early admin bootstrap reuses readiness lifecycle');
  f.doc.readyState='interactive';f.doc.emit('DOMContentLoaded');await flush();
  eq(f.requests.length,1,'Validated footer starts existing controller');
  f.requests[0].respond(envelope(f.requests[0].id));await flush();await f.advance(32);
  f.items.forEach(item=>eq(item.root.style.getPropertyValue(P),'#123','Bootstrap reaches same DIV/iframe styling'));
  boot.stop();
  const fallback=setup(),fc=api.create(fallback.win,fallback.manifest);fc.start();await flush();
  const request=fallback.requests[0], core=envelope(request.id,'',true);
  request.respond(core);await flush();fallback.items.forEach(item=>eq(item.root.style.getPropertyValue(P),'','Global/Core fallback does not inject colors'));fc.stop();
  const suspended=setup(),sc=api.create(suspended.win,suspended.manifest);sc.start();await flush();
  const sr=suspended.requests[0], body=envelope(sr.id);body.appearance={admission:{permitted:false,reason:'snordian_active'},theme:null,color_declarations:'',density:null};
  sr.respond(body);await flush();suspended.items.forEach(item=>eq(item.root.style.getPropertyValue(P),'','SNORDIAN descriptor suspends admin styling'));sc.stop();
  const changed=setup(),cc=api.create(changed.win,changed.manifest);cc.start();await flush();changed.requests[0].respond(envelope(changed.requests[0].id));await flush();
  changed.win.location=new URL('https://fixture.test/wp-admin/profile.php');await changed.mutate();
  changed.items.forEach(item=>eq(item.root.style.getPropertyValue(P),'','Moving to an unrelated admin context releases appearance'));cc.stop();
  console.log('G2 admin JavaScript: '+checks+' assertions passed; shared controller/DOM/frame/fetch fixtures, no live browser.');
}
run().catch(error=>{console.error(error);process.exitCode=1;});
