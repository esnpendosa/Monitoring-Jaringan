<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>FTTH Network Manager — Rozitech</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="https://tile.openstreetmap.org">
<link rel="dns-prefetch" href="https://basemaps.cartocdn.com">
<link rel="stylesheet" href="{{ asset('libs/leaflet/leaflet.min.css') }}">
<link rel="stylesheet" href="{{ asset('libs/leaflet-draw/leaflet.draw.min.css') }}">
<link rel="stylesheet" href="{{ asset('libs/leaflet-cluster/MarkerCluster.min.css') }}">
<link rel="stylesheet" href="{{ asset('libs/leaflet-cluster/MarkerCluster.Default.min.css') }}">
<link rel="stylesheet" href="{{ asset('libs/boxicons/css/boxicons.min.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<style>
:root{
  --bg:#f8fafc;--sb:#ffffff;--card:#ffffff;--bd:#e2e8f0;
  --acc:#2563eb;--green:#16a34a;--yellow:#d97706;--red:#dc2626;
  --text:#0f172a;--muted:#64748b;--r:8px;
  --topbar-h:48px;--sb-w:240px;--rp-w:270px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);overflow:hidden;}

/* ── TOPBAR (LIGHT THEME) ────────────────── */
#topbar{
  position:fixed;top:0;left:0;right:0;height:var(--topbar-h);z-index:2000;
  background:rgba(255,255,255,.96);backdrop-filter:blur(12px);
  border-bottom:1px solid var(--bd);
  display:flex;align-items:center;gap:3px;padding:0 8px;
  box-shadow:0 1px 3px rgba(0,0,0,.05);
  overflow-x:auto;overflow-y:hidden;scrollbar-width:none;-ms-overflow-style:none;
}
#topbar::-webkit-scrollbar{display:none;}
.brand{
  font-size:13px;font-weight:700;color:var(--acc);white-space:nowrap;padding-right:8px;
  border-right:1px solid var(--bd);display:flex;align-items:center;gap:5px;flex-shrink:0;
}
.tb-btn{
  height:28px;padding:0 7px;border-radius:6px;border:none;
  font-size:10.5px;font-weight:600;cursor:pointer;font-family:inherit;
  display:flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0;
  transition:all .15s;
}
.tb-btn.green{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
.tb-btn.green:hover{background:#bbf7d0;}
.tb-btn.blue{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}
.tb-btn.blue:hover{background:#bfdbfe;}
.tb-btn.gold{background:#fef3c7;color:#b45309;border:1px solid #fde68a;}
.tb-btn.gold:hover{background:#fde68a;}
.tb-btn.teal{background:#ccfbf1;color:#0f766e;border:1px solid #99f6e4;}
.tb-btn.teal:hover{background:#99f6e4;}
.tb-btn.red{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;}
.tb-btn.red:hover{background:#fca5a5;}
.tb-btn.purple{background:#f3e8ff;color:#6b21a8;border:1px solid #e9d5ff;}
.tb-btn.purple:hover{background:#e9d5ff;}

.queue-badge{
  height:28px;padding:0 7px;border-radius:6px;background:#fee2e2;color:#b91c1c;
  border:1px solid #fca5a5;font-size:10.5px;font-weight:700;
  display:flex;align-items:center;gap:4px;white-space:nowrap;cursor:pointer;flex-shrink:0;
}
.queue-badge .num{font-size:11px;}
.tb-sep{width:1px;height:22px;background:var(--bd);margin:0 1px;flex-shrink:0;}
.icon-btn{
  height:28px;padding:0 7px;border-radius:6px;border:1px solid var(--bd);
  background:#ffffff;color:var(--text);font-size:10.5px;cursor:pointer;
  font-family:inherit;white-space:nowrap;display:flex;align-items:center;gap:4px;
  text-decoration:none;transition:all .15s;flex-shrink:0;
}
.icon-btn:hover{background:#f1f5f9;border-color:var(--acc);color:var(--acc);}
.notif-btn{
  position:relative;width:28px;height:28px;border-radius:6px;border:1px solid var(--bd);
  background:#ffffff;cursor:pointer;display:flex;align-items:center;justify-content:center;
  font-size:14px;color:var(--text);transition:all .15s;flex-shrink:0;
}
.notif-btn:hover{background:#f1f5f9;}
.notif-btn .badge{
  position:absolute;top:-4px;right:-4px;background:var(--red);color:#fff;
  font-size:9px;font-weight:700;min-width:16px;height:16px;
  border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px;
}

@keyframes cableDashFlow {
  from { stroke-dashoffset: 32px; }
  to { stroke-dashoffset: 0px; }
}
.animated-cable {
  stroke-dasharray: 10, 6 !important;
  animation: cableDashFlow 0.8s linear infinite !important;
}

/* ── MAIN LAYOUT ────────────────────────── */
#main{
  position:fixed;top:var(--topbar-h);left:0;right:0;bottom:0;
  display:flex;
}

/* ── LEFT SIDEBAR (LIGHT) ───────────────── */
#sidebar{
  width:var(--sb-w);flex-shrink:0;background:var(--sb);
  border-right:1px solid var(--bd);display:flex;flex-direction:column;
  transition:width .25s;overflow:hidden;z-index:1000;
}
#sidebar.collapsed{width:0;}
.tab-pills{
  display:flex;padding:6px;gap:2px;border-bottom:1px solid var(--bd);
  flex-wrap:wrap;background:#f8fafc;
}
.tp{
  padding:5px 7px;border-radius:5px;border:none;background:transparent;
  color:var(--muted);font-size:10px;font-weight:600;cursor:pointer;
  font-family:inherit;transition:all .15s;letter-spacing:.3px;
  display:flex;align-items:center;gap:3px;
}
.tp.active,.tp:hover{background:var(--acc);color:#fff;}
#sidebar-body{flex:1;overflow-y:auto;padding:6px;background:#ffffff;}
#sidebar-body::-webkit-scrollbar{width:4px;}
#sidebar-body::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:2px;}
.sec-hdr{
  display:flex;align-items:center;justify-content:space-between;
  padding:6px 4px 4px;font-size:9px;font-weight:700;color:var(--muted);
  letter-spacing:.8px;text-transform:uppercase;
}
.add-btn{
  padding:3px 8px;border-radius:4px;border:1px solid rgba(37,99,235,.3);
  background:rgba(37,99,235,.08);color:var(--acc);font-size:10px;
  cursor:pointer;font-family:inherit;transition:all .15s;
  display:flex;align-items:center;gap:3px;
}
.add-btn:hover{background:rgba(37,99,235,.2);}
.ni{
  display:flex;align-items:center;gap:7px;padding:7px 8px;
  border-radius:6px;cursor:pointer;border:1px solid transparent;
  transition:all .12s;margin-bottom:2px;background:#ffffff;
}
.ni:hover{background:#f1f5f9;border-color:var(--bd);}
.ni.sel{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.25);}
.ni-icon{font-size:14px;color:var(--muted);flex-shrink:0;}
.ni-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.ni-name{font-size:11px;font-weight:500;color:#0f172a;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ni-badge{font-size:9px;padding:1px 5px;border-radius:3px;flex-shrink:0;font-weight:600;}
.s-on{background:#dcfce7;color:#15803d;}
.s-wa{background:#fef3c7;color:#b45309;}
.s-of{background:#fee2e2;color:#b91c1c;}
.tc{display:none;}
.tc.active{display:block;}

/* ── MAP WRAP ───────────────────────────── */
#map-wrap{
  flex:1;position:relative;overflow:hidden;
  background:#f8fafc;
}
#ftth-map{width:100%;height:100%;background:#f8fafc;}
.leaflet-control-attribution{display:none !important;}

/* ── MAP CONTROLS (right float - LIGHT) ─── */
#map-ctrls{
  position:absolute;top:10px;right:16px;z-index:900;
  display:flex;flex-direction:column;gap:4px;
  max-height:calc(100vh - 80px);overflow-y:auto;
  scrollbar-width:none;-ms-overflow-style:none;
}
#map-ctrls::-webkit-scrollbar{display:none;}
.mc{
  width:34px;height:34px;border-radius:8px;border:1px solid #cbd5e1;
  background:#ffffff;color:#0f172a;cursor:pointer;font-size:16px;
  display:flex;align-items:center;justify-content:center;transition:all .15s;
  position:relative;box-shadow:0 2px 8px rgba(0,0,0,.2);flex-shrink:0;
}
.mc:hover,.mc.on{background:#e0f2fe;border-color:#0284c7;color:#0284c7;}
.mc .tt{
  position:absolute;right:40px;white-space:nowrap;
  background:rgba(255,255,255,.98);color:var(--text);font-size:10px;
  padding:3px 8px;border-radius:4px;border:1px solid var(--bd);
  opacity:0;pointer-events:none;transition:opacity .15s;
  box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.mc:hover .tt{opacity:1;}
.mc-sep{height:1px;background:var(--bd);margin:2px 0;}

/* ── DRAW BANNER (LIGHT) ────────────────── */
#draw-banner{
  position:absolute;top:8px;left:50%;transform:translateX(-50%);z-index:910;
  background:rgba(255,255,255,.96);border:1px solid rgba(37,99,235,.4);
  color:var(--text);padding:8px 16px;border-radius:10px;font-size:11px;
  backdrop-filter:blur(12px);display:none;align-items:center;gap:10px;white-space:nowrap;
  box-shadow:0 8px 30px rgba(0,0,0,.12);
}
#draw-banner.show{display:flex;}
.draw-cancel{
  background:none;border:none;color:var(--red);cursor:pointer;font-size:13px;margin-left:4px;
}

/* ── LEGEND (LIGHT) ─────────────────────── */
#legend{
  position:absolute;bottom:36px;left:8px;z-index:900;
  background:rgba(255,255,255,.95);backdrop-filter:blur(8px);
  border:1px solid var(--bd);border-radius:var(--r);padding:10px 12px;
  font-size:10px;min-width:180px;box-shadow:0 4px 12px rgba(0,0,0,.08);
}
.leg-r{display:flex;align-items:center;gap:8px;margin-bottom:5px;}
.leg-r:last-child{margin-bottom:0;}
.leg-l{width:22px;height:4px;border-radius:2px;flex-shrink:0;}
.leg-c{width:10px;height:10px;border-radius:50%;flex-shrink:0;}

/* ── STATUS BAR (LIGHT) ─────────────────── */
#statusbar{
  position:absolute;bottom:0;left:0;right:0;height:34px;z-index:900;
  background:rgba(255,255,255,.96);backdrop-filter:blur(8px);
  border-top:1px solid var(--bd);
  display:flex;align-items:center;gap:12px;padding:0 12px;font-size:10.5px;
  overflow:hidden;
}
#statusbar span{display:flex;align-items:center;gap:4px;color:var(--muted);white-space:nowrap;}
#statusbar .on-c{color:var(--green);font-weight:700;}
#statusbar .of-c{color:var(--red);font-weight:700;}
#statusbar .coords{margin-left:auto;color:var(--muted);white-space:nowrap;}
.mode-grp{
  display:flex;align-items:center;gap:8px;margin-left:8px;
  background:#f1f5f9;border:1px solid var(--bd);
  border-radius:6px;padding:2px 8px;flex-shrink:0;
}
.mode-grp label{display:flex;align-items:center;gap:3px;cursor:pointer;font-size:10px;color:var(--muted);white-space:nowrap;}
.mode-grp input{accent-color:var(--acc);}
.mode-grp label:hover{color:var(--text);}
.mc-sep{height:1px;background:#e2e8f0;margin:3px 0;width:100%;flex-shrink:0;}

/* ── RIGHT PANEL (LIGHT) ────────────────── */
#right-panel{
  width:var(--rp-w);flex-shrink:0;background:var(--sb);
  border-left:1px solid var(--bd);display:flex;flex-direction:column;overflow:hidden;
}
.rp-tabs{display:flex;border-bottom:1px solid var(--bd);background:#f8fafc;}
.rp-tab{
  flex:1;padding:8px;border:none;background:transparent;color:var(--muted);
  font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;
  display:flex;align-items:center;justify-content:center;gap:4px;
}
.rp-tab.active{background:var(--acc);color:#fff;}
.rp-actions{display:flex;gap:4px;padding:6px;}
.rp-btn{
  flex:1;padding:6px 0;border-radius:6px;border:1px solid var(--bd);
  font-size:10px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;
  display:flex;align-items:center;justify-content:center;gap:4px;
}
.rp-btn.wa{color:#16a34a;border-color:rgba(22,163,74,.3);background:rgba(22,163,74,.07);}
.rp-btn.wa:hover{background:rgba(22,163,74,.15);}
.rp-btn.tg{color:#0284c7;border-color:rgba(2,132,199,.3);background:rgba(2,132,199,.07);}
.rp-btn.tg:hover{background:rgba(2,132,199,.15);}
.rp-hdr{
  padding:6px 8px;font-size:9px;font-weight:700;letter-spacing:.7px;
  color:var(--muted);text-transform:uppercase;border-bottom:1px solid var(--bd);
  display:flex;align-items:center;gap:6px;background:#f8fafc;
}
.rp-hdr .cnt{color:var(--red);}
#offline-list{flex:1;overflow-y:auto;padding:4px;background:#ffffff;}
#offline-list::-webkit-scrollbar{width:4px;}
#offline-list::-webkit-scrollbar-thumb{background:#cbd5e1;}
.oi{
  padding:7px 8px;border-bottom:1px solid #f1f5f9;
  cursor:pointer;transition:background .12s;
}
.oi:hover{background:#f8fafc;}
.oi-name{font-size:11px;font-weight:600;color:var(--text);margin-bottom:2px;}
.oi-ip{font-size:10px;color:var(--muted);font-family:monospace;}
.oi-dur{font-size:10px;color:var(--red);font-weight:600;float:right;}

/* ── POPUP (LIGHT THEME FIX) ────────────── */
.leaflet-popup-content-wrapper{
  background:#ffffff!important;border:1px solid #cbd5e1!important;
  border-radius:10px!important;color:var(--text)!important;
  box-shadow:0 10px 30px rgba(0,0,0,.15)!important;padding:0!important;
  overflow:hidden!important;
}
.leaflet-popup-tip-container{display:none!important;}
.leaflet-popup-content{margin:0!important;width:290px!important;max-width:290px!important;}
.p-hdr{
  display:flex;align-items:center;gap:6px;padding:8px 10px;
  background:#f8fafc;border-bottom:1px solid var(--bd);
}
.p-hdr h6{font-size:11px;font-weight:700;flex:1;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a;}
.p-badge{
  font-size:9px;padding:2px 6px;border-radius:4px;flex-shrink:0;
  background:#dcfce7;color:#15803d;font-weight:600;
}
.p-badge.offline{background:#fee2e2;color:#b91c1c;}
.p-close{background:none;border:none;color:var(--muted);cursor:pointer;font-size:16px;padding:0 2px;}
.p-body{padding:8px 10px;background:#ffffff;}
.p-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;font-size:11px;}
.p-row .lbl{color:var(--muted);display:flex;align-items:center;gap:4px;}
.p-row .val{font-weight:600;color:#0f172a;}
.p-row .val.mono{font-family:monospace;font-size:10px;}
.p-row .val.on{color:var(--green);}
.p-row .val.off{color:var(--red);}
.ping-btn{
  padding:2px 8px;border-radius:4px;border:1px solid rgba(37,99,235,.4);
  background:rgba(37,99,235,.1);color:var(--acc);font-size:10px;cursor:pointer;
  font-family:inherit;transition:all .15s;display:flex;align-items:center;gap:3px;
}
.ping-btn:hover{background:rgba(37,99,235,.2);}
.p-divider{height:1px;background:var(--bd);margin:6px 0;}
.p-redaman-row{
  display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:6px;
}
.p-redaman-box{
  background:#f8fafc;border:1px solid var(--bd);border-radius:6px;
  padding:5px 6px;text-align:center;
}
.p-redaman-box .r-lbl{font-size:9px;color:var(--muted);margin-bottom:1px;}
.p-redaman-box .r-val{font-size:11px;font-weight:700;}
.p-redaman-box .r-val.ok{color:var(--green);}
.p-redaman-box .r-val.wa{color:var(--yellow);}
.p-redaman-box .r-val.ba{color:var(--red);}
.p-wifi-row{
  display:flex;align-items:center;gap:6px;margin-bottom:3px;font-size:10px;
}
.p-wifi-row .lbl{color:var(--muted);width:65px;flex-shrink:0;}
.p-wifi-row .val{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;color:#0f172a;}
.p-wifi-row .pass{letter-spacing:1px;font-size:9px;}
.p-wifi-actions{display:flex;gap:4px;margin-bottom:6px;margin-top:4px;}
.p-wa{
  flex:1;padding:4px;border-radius:4px;border:1px solid;font-size:10px;font-weight:600;
  cursor:pointer;font-family:inherit;text-align:center;transition:all .15s;
  display:flex;align-items:center;justify-content:center;gap:3px;
}
.p-wa.chg{border-color:rgba(22,163,74,.4);background:#dcfce7;color:#15803d;}
.p-wa.chg:hover{background:#bbf7d0;}
.p-wa.rbt{border-color:rgba(220,38,38,.4);background:#fee2e2;color:#b91c1c;}
.p-wa.rbt:hover{background:#fca5a5;}
.p-traffic{
  margin-top:4px;margin-bottom:6px;background:#f8fafc;
  border:1px solid var(--bd);border-radius:6px;padding:5px;
}
.p-traffic .t-hdr{font-size:9px;color:var(--muted);margin-bottom:3px;display:flex;align-items:center;justify-content:space-between;}
.chart-container{height:45px;max-height:45px;position:relative;width:100%;}
.p-actions{display:flex;gap:3px;flex-wrap:nowrap;}
.p-act{
  flex:1;padding:4px 0;border-radius:4px;border:1px solid var(--bd);
  background:#ffffff;color:var(--text);font-size:9px;font-weight:600;
  cursor:pointer;font-family:inherit;text-align:center;transition:all .15s;
  display:flex;align-items:center;justify-content:center;gap:2px;white-space:nowrap;
}
.p-act:hover{border-color:var(--acc);color:var(--acc);background:#f1f5f9;}
.p-act.edit{background:#dbeafe;border-color:#bfdbfe;color:#1d4ed8;}
.p-act.dup{background:#f3e8ff;border-color:#e9d5ff;color:#6b21a8;}

/* ── MODALS (LIGHT THEME) ───────────────── */
.modal-backdrop{
  position:fixed;inset:0;z-index:3000;background:rgba(15,23,42,.6);
  backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;padding:12px;
}
.modal-backdrop.open{display:flex;}
.modal{
  background:#ffffff;border:1px solid var(--bd);border-radius:14px;
  min-width:320px;max-width:600px;width:100%;max-height:90vh;
  display:flex;flex-direction:column;box-shadow:0 25px 70px rgba(0,0,0,.22);
  transition:all .2s ease;
}
.modal.wide{max-width:1180px;width:96%;height:92vh;}
.modal.fullscreen{
  max-width:100vw !important;width:100vw !important;height:100vh !important;
  max-height:100vh !important;border-radius:0 !important;margin:0 !important;
}
.modal.term{max-width:680px;}
.m-hdr{
  padding:12px 18px;border-bottom:1px solid var(--bd);background:#f8fafc;
  display:flex;align-items:center;justify-content:space-between;flex-shrink:0;border-radius:14px 14px 0 0;
}
.modal.fullscreen .m-hdr{border-radius:0;}
.m-hdr h5{font-size:14px;font-weight:700;margin:0;color:#0f172a;display:flex;align-items:center;gap:6px;}
.m-icon-btn{
  background:transparent;border:1px solid var(--bd);border-radius:6px;
  color:var(--muted);cursor:pointer;font-size:14px;width:28px;height:28px;
  display:flex;align-items:center;justify-content:center;transition:all .15s;
}
.m-icon-btn:hover{background:#f1f5f9;color:var(--acc);border-color:var(--acc);}
.m-close{background:none;border:none;color:var(--muted);cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;}
.m-close:hover{background:#fee2e2;color:#ef4444;}
.m-body{padding:16px;overflow-y:auto;flex:1;background:#ffffff;}
.m-foot{padding:10px 18px;border-top:1px solid var(--bd);background:#f8fafc;display:flex;gap:8px;justify-content:flex-end;flex-shrink:0;border-radius:0 0 14px 14px;}
.modal.fullscreen .m-foot{border-radius:0;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;}
.fg{margin-bottom:10px;}
.fg label{display:block;font-size:11px;color:var(--muted);margin-bottom:4px;}
.fg input,.fg select,.fg textarea{
  width:100%;background:#f8fafc;border:1px solid #cbd5e1;
  border-radius:6px;color:#0f172a;font-size:12px;padding:7px 10px;
  font-family:inherit;outline:none;transition:border-color .15s;
}
.fg input:focus,.fg select:focus{border-color:var(--acc);background:#ffffff;}
.btn-p{
  padding:7px 16px;border-radius:6px;border:none;background:var(--acc);
  color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;
  transition:opacity .15s;display:flex;align-items:center;gap:4px;
}
.btn-p:hover{opacity:.88;}
.btn-s{
  padding:7px 16px;border-radius:6px;border:1px solid #cbd5e1;background:#ffffff;
  color:var(--text);font-size:12px;cursor:pointer;font-family:inherit;
}

/* Terminal (Light Terminal) */
#terminal-out{
  background:#0f172a;border:1px solid var(--bd);border-radius:6px;
  padding:10px;font-family:'Courier New',monospace;font-size:11px;
  color:#4ade80;min-height:180px;max-height:260px;overflow-y:auto;
  white-space:pre-wrap;word-break:break-all;line-height:1.5;
}
.t-line{display:block;}
.t-line.warn{color:#fbbf24;}
.t-line.err{color:#f87171;}

/* Kalkulator result */
.calc-result{
  background:#f8fafc;border:1px solid var(--bd);border-radius:8px;
  padding:12px;margin-top:10px;
}
.calc-row{display:flex;justify-content:space-between;padding:4px 0;font-size:11px;border-bottom:1px solid #e2e8f0;}
.calc-row:last-child{border-bottom:none;}
.calc-row.total{font-weight:700;color:var(--text);padding-top:6px;}
.calc-row.rx-ok{color:var(--green);}
.calc-row.rx-wa{color:var(--yellow);}
.calc-row.rx-cr{color:var(--red);}

/* Data table */
#data-table{width:100%;border-collapse:collapse;font-size:11px;}
#data-table th{
  background:#f8fafc;padding:7px 10px;text-align:left;font-size:10px;
  font-weight:600;color:var(--muted);letter-spacing:.4px;
  position:sticky;top:0;border-bottom:1px solid var(--bd);
}
#data-table td{padding:6px 10px;border-bottom:1px solid #f1f5f9;color:#0f172a;}
#data-table tr:hover td{background:#f1f5f9;}
.tbl-wrap{max-height:400px;overflow-y:auto;border-radius:6px;border:1px solid var(--bd);}
.tbl-wrap::-webkit-scrollbar{width:4px;}
.tbl-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;}

/* Toast */
#toast{
  position:fixed;bottom:56px;left:50%;transform:translateX(-50%) translateY(60px);
  z-index:99999;background:#ffffff;border:1px solid var(--bd);color:var(--text);
  padding:8px 16px;border-radius:8px;font-size:12px;font-weight:500;
  box-shadow:0 10px 30px rgba(0,0,0,.25);
  transition:all .2s ease-in-out;pointer-events:none;
  display:none;align-items:center;gap:6px;opacity:0;visibility:hidden;
}
#toast.show{
  transform:translateX(-50%) translateY(0);
  pointer-events:auto!important;
  display:flex!important;
  opacity:1!important;
  visibility:visible!important;
}
#toast.ok{border-color:rgba(22,163,74,.4);color:var(--green);}
#toast.er{border-color:rgba(220,38,38,.4);color:var(--red);}

/* Leaflet tooltip cable & distance pills (CLEAN PROFESSIONAL GIS) */
.leaflet-tooltip.cl{
  background:#ffffff!important;
  border:1px solid #cbd5e1!important;
  color:#1e293b!important;
  font-size:9.5px!important;
  font-family:'Inter',system-ui,sans-serif!important;
  padding:3px 8px!important;
  border-radius:4px!important;
  white-space:nowrap!important;
  box-shadow:0 2px 6px rgba(0,0,0,.08)!important;
  line-height:1.3!important;
}
.leaflet-tooltip.cl::before{display:none!important;}

.cable-dist-pill{
  background:#ffffff!important;
  border:1px solid #cbd5e1!important;
  color:#475569!important;
  font-size:8.5px!important;
  font-weight:600!important;
  padding:1px 4px!important;
  border-radius:3px!important;
  white-space:nowrap!important;
  box-shadow:0 1px 4px rgba(0,0,0,.06)!important;
  font-family:'Inter',system-ui,sans-serif!important;
}
.cable-dist-pill::before{display:none!important;}

/* SLEEK MARKER CLUSTER */
.lmc{
  width:32px!important;height:32px!important;
  background:rgba(37,99,235,.9)!important;border:2px solid #ffffff!important;
  border-radius:50%!important;display:flex!important;align-items:center!important;
  justify-content:center!important;color:#fff!important;font-weight:700!important;
  font-size:11px!important;font-family:'Inter',sans-serif!important;
  box-shadow:0 2px 8px rgba(37,99,235,.4)!important;
}

/* CUSTOM GIS MARKERS (LIGHT THEME) */
.gis-marker{
  display:flex;align-items:center;justify-content:center;
  border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.15);
  border:2px solid #ffffff;cursor:pointer;
  transition:transform .15s;
}
.gis-marker.olt{background:#2563eb;color:#fff;font-size:18px;}
.gis-marker.ont{background:#16a34a;color:#fff;font-size:14px;}
.gis-marker.ont.off{background:#dc2626;}

/* ODP YELLOW BOX MARKER */
.odp-yellow-box{
  width:36px;height:36px;background:#facc15;border:2px solid #ca8a04;
  border-radius:8px;display:flex;flex-direction:column;align-items:center;
  justify-content:center;color:#0f172a;font-weight:800;
  box-shadow:0 3px 10px rgba(0,0,0,.15);cursor:pointer;
}
.odp-yellow-box.off{background:#dc2626;border-color:#991b1b;color:#fff;}
.odp-yellow-box.wa{background:#f59e0b;border-color:#b45309;color:#fff;}
.odp-yellow-box .odp-num{font-size:9px;font-weight:700;line-height:1;margin-top:1px;}

/* ODC ORANGE BOX MARKER */
.odc-box-marker{
  width:38px;height:38px;background:#f97316;border:2px solid #ea580c;
  border-radius:8px;display:flex;flex-direction:column;align-items:center;
  justify-content:center;color:#fff;font-weight:800;
  box-shadow:0 3px 10px rgba(0,0,0,.15);cursor:pointer;
}

/* PROFESSIONAL UNIFIED NODE LABEL PILL */
.node-label-pill{
  background:#ffffff;
  border:1px solid #cbd5e1;
  color:#0f172a;
  font-size:9.5px;
  font-weight:600;
  padding:2px 6px;
  border-radius:4px;
  white-space:nowrap;
  text-align:center;
  margin-top:3px;
  box-shadow:0 1px 4px rgba(0,0,0,.08);
  font-family:'Inter',system-ui,sans-serif;
  line-height:1.2;
}
.sub-desc{
  display:block;
  font-size:8.5px;
  font-weight:400;
  color:#64748b;
  margin-top:2px;
  border-top:1px dashed #e2e8f0;
  padding-top:2px;
  max-width:160px;
  white-space:normal;
  word-break:break-word;
  line-height:1.2;
}
.node-sublabel-pill{
  background:#0f172a;border:1px solid #334155;
  color:#38bdf8;font-size:8.5px;font-weight:600;padding:1px 5px;border-radius:3px;
  white-space:nowrap;text-align:center;margin-top:2px;
  box-shadow:0 2px 5px rgba(0,0,0,.18);font-family:'Inter',sans-serif;
}
</style>
<body>

<!-- Instant App Loader Overlay -->
<div id="app-loader" style="position:fixed;top:0;left:0;right:0;bottom:0;background:#ffffff;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity .25s ease;">
  <div style="font-size:18px;font-weight:700;color:#2563eb;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
    <i class="bx bx-network-chart"></i> FTTH Manager — Rozitech
  </div>
  <div style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:6px;">
    <i class="bx bx-loader-alt bx-spin" style="font-size:16px;"></i> Memuat Peta & Data Jaringan...
  </div>
</div>
<div id="topbar">
  <div class="brand"><i class="bx bx-network-chart"></i> FTTH Manager</div>

  <button class="tb-btn green" id="btn-sync-mt" onclick="syncMikrotik()"><i class="bx bx-refresh"></i> Sync Mikrotik</button>
  <button class="tb-btn blue"  id="btn-sync-gc" onclick="openAcsConfigModal()"><i class="bx bx-broadcast"></i> Sync GenieACS</button>
  <button class="tb-btn gold"  onclick="openModal('m-backup')"><i class="bx bx-data"></i> Backup/Restore</button>
  <button class="tb-btn purple" onclick="openModal('m-perangkat')"><i class="bx bx-devices"></i> Perangkat</button>
  <button class="tb-btn teal"  onclick="openTabelOnu()"><i class="bx bx-table"></i> Tabel ONU</button>
  <button class="tb-btn green" onclick="openKalkulatorRedaman()"><i class="bx bx-calculator"></i> Kalkulator Redaman</button>
  <button class="tb-btn teal"  onclick="openAutoTiangModal()"><i class="bx bx-map-pin"></i> Auto Tiang</button>
  <div class="queue-badge" onclick="openModal('m-queue')"><i class="bx bx-list-ol"></i> Queue <span class="num" id="queue-num">—</span></div>



  <button class="icon-btn" onclick="openModal('m-backup')"><i class="bx bx-upload"></i> Import KMZ</button>
  <button class="icon-btn" onclick="exportCsv()"><i class="bx bx-download"></i> Export CSV</button>
  <button class="icon-btn" onclick="toggleFullscreen()"><i class="bx bx-fullscreen"></i></button>
  <button class="notif-btn" onclick="openModal('m-notif')">
    <i class="bx bx-bell"></i><span class="badge" id="notif-badge" style="display:none;">0</span>
  </button>
  <a href="{{ route('dashboard') }}" class="icon-btn"><i class="bx bx-arrow-back"></i> Dashboard</a>
</div>

<!-- ══════════════════════════════════════ MAIN ════════════════════ -->
<div id="main">

  <!-- LEFT SIDEBAR -->
  <div id="sidebar">
    <div class="tab-pills">
      <button class="tp active" data-tab="olt" onclick="switchTab('olt', this)"><i class="bx bx-server"></i> OLT</button>
      <button class="tp" data-tab="odc" onclick="switchTab('odc', this)"><i class="bx bx-cube-alt"></i> ODC</button>
      <button class="tp" data-tab="odp" onclick="switchTab('odp', this)"><i class="bx bx-box"></i> ODP</button>
      <button class="tp" data-tab="kabel" onclick="switchTab('kabel', this)"><i class="bx bx-git-commit"></i> Kabel</button>
      <button class="tp" data-tab="ont" onclick="switchTab('ont', this)"><i class="bx bx-home-alt"></i> ONT</button>
      <button class="tp" data-tab="item" onclick="switchTab('item', this)"><i class="bx bx-map-pin"></i> Item</button>
    </div>
    <div id="sidebar-body">
      <div id="tc-olt"   class="tc active"></div>
      <div id="tc-odc"   class="tc"></div>
      <div id="tc-odp"   class="tc"></div>
      <div id="tc-kabel" class="tc"></div>
      <div id="tc-ont"   class="tc"></div>
      <div id="tc-item"  class="tc"></div>
    </div>
  </div>

  <!-- MAP -->
  <div id="map-wrap">
    <div id="ftth-map"></div>

    <!-- Draw banner -->
    <div id="draw-banner">
      <i class="bx bx-edit-alt" style="color:var(--acc);font-size:16px;"></i>
      <b>Drawing Jalur Kabel</b> — Klik titik-titik di peta.
      <label style="margin-left:8px;font-size:11px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;color:#d97706;">
        <input type="checkbox" id="cb-auto-route" checked style="accent-color:var(--acc);"> Ikuti Rute Jalan (OSRM Road Routing)
      </label>
      <button class="btn-p" style="padding:3px 12px;font-size:11px;margin-left:8px;background:#16a34a;" onclick="finishDraw()"><i class="bx bx-check-circle"></i> Selesai & Simpan</button>
      <button class="draw-cancel" onclick="cancelDraw()"><i class="bx bx-x"></i> Batal</button>
    </div>



    <!-- Right controls -->
    <div id="map-ctrls">
      <button class="mc on" id="mc-olt"   onclick="toggleLyr('olt')"  ><i class="bx bx-server"></i><span class="tt">OLT</span></button>
      <button class="mc on" id="mc-odc"   onclick="toggleLyr('odc')"  ><i class="bx bx-cube-alt"></i><span class="tt">ODC</span></button>
      <button class="mc on" id="mc-odp"   onclick="toggleLyr('odp')"  ><i class="bx bx-box"></i><span class="tt">ODP</span></button>
      <button class="mc on" id="mc-cable" onclick="toggleLyr('cable')" ><i class="bx bx-git-commit"></i><span class="tt">Kabel</span></button>
      <button class="mc on" id="mc-ont"   onclick="toggleLyr('ont')"  ><i class="bx bx-home-alt"></i><span class="tt">ONT</span></button>
      <button class="mc on" id="mc-item"  onclick="toggleLyr('item')" ><i class="bx bx-map-pin"></i><span class="tt">Item</span></button>
      <button class="mc on" id="mc-lbl"   onclick="toggleLabels()"    ><i class="bx bx-purchase-tag-alt"></i><span class="tt">Label Kabel</span></button>
      <button class="mc on" id="mc-anim"  onclick="toggleAnim()"      ><i class="bx bx-bolt"></i><span class="tt">Animasi Kabel</span></button>
      <div class="mc-sep"></div>
      <button class="mc" onclick="quickAddSymbol('tiang_loop')" style="border-color:#0f172a;color:#0f172a;" title="Tambah Tiang Loop Fiber (Simbol 1 - Sketsa Kiri)"><i class="bx bx-radio-circle-marked"></i><span class="tt">+ Tiang Loop</span></button>
      <button class="mc" onclick="quickAddSymbol('slack_loop')" style="border-color:#0f172a;color:#0f172a;" title="Tambah Oval Joint Closure (Simbol 2 - Sketsa Tengah)"><i class="bx bx-infinite"></i><span class="tt">+ Oval Closure</span></button>
      <button class="mc" onclick="quickAddSymbol('tiang_tumpu')" style="border-color:#0f172a;color:#0f172a;" title="Tambah Tiang Tumpu T-Bar (Simbol 3 - Sketsa Kanan)"><i class="bx bx-plus-medical"></i><span class="tt">+ Tiang Tumpu</span></button>
      <button class="mc" onclick="startDrawCable()"  ><i class="bx bx-edit-alt"></i><span class="tt">Gambar Kabel</span></button>
      <button class="mc" onclick="openPingTerminal()"><i class="bx bx-terminal"></i><span class="tt">Ping Terminal</span></button>
      <button class="mc" onclick="startMeasure()"    ><i class="bx bx-ruler"></i><span class="tt">Ukur Jarak</span></button>
      <button class="mc" onclick="fitAll()"          ><i class="bx bx-target-lock"></i><span class="tt">Fit Semua</span></button>
    </div>

    <!-- Status bar -->
    <div id="statusbar">
      <span><i class="bx bx-server"></i> OLT: <b id="st-olt">—</b></span>
      <span><i class="bx bx-cube-alt"></i> ODC: <b id="st-odc">—</b></span>
      <span><i class="bx bx-box"></i> ODP: <b id="st-odp">—</b></span>
      <span><i class="bx bx-git-commit"></i> Kabel: <b id="st-kab">—</b></span>
      <span><i class="bx bx-check-circle"></i> Online: <b class="on-c" id="st-on">—</b></span>
      <span><i class="bx bx-x-circle"></i> Offline: <b class="of-c" id="st-off">—</b></span>
      <span id="st-sync" style="color:var(--muted);font-size:10px;"></span>
      <span class="coords" id="st-coords"></span>
      <div class="mode-grp">
        <label><input type="radio" name="mapmode" value="osm" checked onchange="setMapMode('osm')"> Light</label>
        <label><input type="radio" name="mapmode" value="sat" onchange="setMapMode('sat')"> Satelit</label>
        <label><input type="radio" name="mapmode" value="dark" onchange="setMapMode('dark')"> Dark</label>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div id="right-panel">
    <div class="rp-tabs">
      <button class="rp-tab active" id="rp-terlama" onclick="setRpSort('terlama', this)"><i class="bx bx-sort-up"></i> Terlama</button>
      <button class="rp-tab" id="rp-terbaru" onclick="setRpSort('terbaru', this)"><i class="bx bx-sort-down"></i> Terbaru</button>
    </div>

    <div class="rp-hdr">ONU OFFLINE <span class="cnt">(<span id="offline-count">0</span>)</span></div>
    <div id="offline-list"><div style="padding:16px;text-align:center;color:var(--muted);font-size:11px;">Memuat data...</div></div>
  </div>

</div><!-- #main -->

<!-- DEDICATED WHATSAPP MULTI-DEVICE MANAGER MODAL -->
<div class="modal-backdrop" id="m-wa-config" style="display:none;" onclick="if(event.target===this)closeModal('m-wa-config')">
  <div class="modal" style="width:92%;max-width:1150px;height:85vh;max-height:820px;display:flex;flex-direction:column;padding:0;overflow:hidden;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,0.3);background:#fff;">
    <div class="m-hdr" style="padding:12px 18px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between;background:#f8fafc;">
      <h5 style="margin:0;font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px;color:#16a34a;">
        <i class="bx bxl-whatsapp" style="font-size:20px;"></i> WhatsApp Multi-Device Manager & Server Bot
      </h5>
      <button class="m-close" onclick="closeModal('m-wa-config')"><i class="bx bx-x"></i></button>
    </div>
    <iframe src="{{ route('whatsapp.index') }}" style="flex:1;width:100%;border:none;background:#ffffff;"></iframe>
  </div>
</div>

<!-- DEDICATED KABEL SAVE MODAL -->
<div class="modal-backdrop" id="m-simpan-kabel">
  <div class="modal" style="max-width:460px;">
    <div class="m-hdr">
      <h5><i class="bx bx-git-commit"></i> Simpan Jalur Kabel Baru</h5>
      <button class="m-close" onclick="closeModal('m-simpan-kabel')"><i class="bx bx-x"></i></button>
    </div>
    <div class="m-body">
      <div class="form-row">
        <div class="fg">
          <label>Titik Asal (From Node)</label>
          <select id="nk-from" onchange="autoGenCableLabel()"></select>
        </div>
        <div class="fg">
          <label>Titik Tujuan (To Node)</label>
          <select id="nk-to" onchange="autoGenCableLabel()"></select>
        </div>
      </div>
      <div class="fg">
        <label>Label / Nama Kabel</label>
        <input id="nk-label" type="text" placeholder="odc-c320-c1. - odp-c320-c1-c1">
      </div>
      <div class="fg">
        <label>Tipe Kabel & Warna Standard</label>
        <select id="nk-tipe" onchange="onCableTypeSelectChange()">
          <option value="feeder">Feeder Cable (Cyan - #0284c7)</option>
          <option value="distribusi" selected>Distribusi Cable (Kuning/Orange - #d97706)</option>
          <option value="backbone">Backbone Cable (Ungu - #8b5cf6)</option>
          <option value="trunk">Trunk Cable (Magenta/Merah - #ec4899)</option>
          <option value="sub_distribusi">Sub-Distribusi Cable (Hijau - #10b981)</option>
          <option value="drop">Drop Core Cable (Biru - #2563eb)</option>
        </select>
      </div>
      <div class="fg">
        <label>Pilih Warna Custom Kabel (Opsional - Realtime Preview)</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="color" id="nk-color-picker" value="#d97706" style="height:36px;width:50px;padding:2px;cursor:pointer;border-radius:6px;border:1px solid var(--bd);" oninput="document.getElementById('nk-color').value=this.value; updateCableRealtimePreview();">
          <input id="nk-color" type="text" placeholder="#d97706 (Opsional - Hex Kode Warna)" style="flex:1;" oninput="updateCableRealtimePreview();">
        </div>
      </div>
      <div class="fg">
        <label>Jumlah Core</label>
        <input id="nk-core" type="number" value="12" min="1">
      </div>
      <div class="fg">
        <label>Monitoring Mode</label>
        <select id="nk-mon">
          <option value="manual">Manual (Status Static)</option>
          <option value="realtime">Realtime Monitoring (RFTS)</option>
        </select>
      </div>
      <div class="fg">
        <label>Catatan / Keterangan</label>
        <textarea id="nk-cat" rows="2" placeholder="Catatan opsional..."></textarea>
      </div>
      <div id="nk-info-box" style="background:#f8fafc;border:1px solid var(--bd);border-radius:6px;padding:8px;font-size:11px;color:var(--text);">
        <!-- Total length and point count filled by JS -->
      </div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-simpan-kabel')">Batal</button>
      <button class="btn-p" id="btn-save-kabel" onclick="submitNewKabel()"><i class="bx bx-save"></i> Simpan Kabel Ke Peta</button>
    </div>
  </div>
</div>

<!-- GenieACS App Management Modal -->
<div class="modal-backdrop" id="m-acs-config">
  <div class="modal wide" id="modal-acs-container">
    <div class="m-hdr">
      <h5><i class="bx bx-broadcast" style="color:var(--acc);font-size:18px;"></i> GenieACS TR-069 App Manager</h5>
      <div style="display:flex;align-items:center;gap:8px;">
        <span id="acs-conn-status" style="font-size:11px;padding:4px 10px;border-radius:20px;background:#dcfce7;color:#15803d;font-weight:700;display:flex;align-items:center;gap:4px;">
          <i class="bx bx-check-circle"></i> Connected (http://localhost:7557)
        </span>
        <button class="m-icon-btn" onclick="toggleModalFullscreen('modal-acs-container')" title="Layar Penuh / Fullscreen">
          <i class="bx bx-fullscreen" id="btn-fullscreen-icon"></i>
        </button>
        <button class="m-close" onclick="closeModal('m-acs-config')" title="Tutup">
          <i class="bx bx-x"></i>
        </button>
      </div>
    </div>
    <div class="m-body" style="padding:14px;">
      <!-- Navigation Tabs (Clean, Scrollable & Mobile-Responsive) -->
      <div style="display:flex;gap:6px;margin-bottom:14px;border-bottom:1px solid var(--bd);padding-bottom:8px;overflow-x:auto;white-space:nowrap;" class="acs-nav-tabs">
        <button class="tp active" id="acs-tab-ovw" onclick="switchAcsTab('ovw', this)"><i class="bx bx-pie-chart-alt-2"></i> Ringkasan Analytics</button>
        <button class="tp" id="acs-tab-dev" onclick="switchAcsTab('dev', this)"><i class="bx bx-devices"></i> Daftar Modem (<span id="acs-dev-cnt">0</span>)</button>
        <button class="tp" id="acs-tab-det" onclick="switchAcsTab('det', this)"><i class="bx bx-chip"></i> Detail & Kontrol WiFi</button>
        <button class="tp" id="acs-tab-pst" onclick="switchAcsTab('pst', this)"><i class="bx bx-slider-alt"></i> Presets</button>
        <button class="tp" id="acs-tab-prv" onclick="switchAcsTab('prv', this)"><i class="bx bx-code-alt"></i> Provisions</button>
        <button class="tp" id="acs-tab-flt" onclick="switchAcsTab('flt', this)"><i class="bx bx-error-circle"></i> Log Gangguan</button>
        <button class="tp" id="acs-tab-fls" onclick="switchAcsTab('fls', this)"><i class="bx bx-folder"></i> Firmware (FS)</button>
        <button class="tp" id="acs-tab-cfg" onclick="switchAcsTab('cfg', this)"><i class="bx bx-cog"></i> Pengaturan NBI</button>
      </div>

      <!-- Tab 0: Overview Analytics (Pie Charts matching GenieACS UI) -->
      <div id="acs-sec-ovw" class="acs-sec">
        <div style="background:#f8fafc;border:1px solid var(--bd);border-radius:8px;padding:12px;margin-bottom:12px;">
          <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;">
            <span><i class="bx bx-pie-chart-alt-2" style="color:var(--acc);"></i> ACS-TR069 || Config & Monitoring Overview</span>
            <button class="btn-p" style="font-size:10px;padding:4px 8px;" onclick="loadAcsOverviewCharts()"><i class="bx bx-refresh"></i> Refresh Analytics</button>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:12px;">
            <div style="background:#fff;border:1px solid var(--bd);border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <h6 style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;">Status Devices</h6>
              <div style="height:130px;position:relative;"><canvas id="chart-acs-status"></canvas></div>
            </div>
            <div style="background:#fff;border:1px solid var(--bd);border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <h6 style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;">Access Type</h6>
              <div style="height:130px;position:relative;"><canvas id="chart-acs-access"></canvas></div>
            </div>
            <div style="background:#fff;border:1px solid var(--bd);border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <h6 style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;">Optical RX Power</h6>
              <div style="height:130px;position:relative;"><canvas id="chart-acs-rx"></canvas></div>
            </div>
            <div style="background:#fff;border:1px solid var(--bd);border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <h6 style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;">Merk Perangkat</h6>
              <div style="height:130px;position:relative;"><canvas id="chart-acs-vendor"></canvas></div>
            </div>
            <div style="background:#fff;border:1px solid var(--bd);border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <h6 style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;">Optical Temperatur</h6>
              <div style="height:130px;position:relative;"><canvas id="chart-acs-temp"></canvas></div>
            </div>
            <div style="background:#fff;border:1px solid var(--bd);border-radius:8px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <h6 style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;">WiFi Connected Clients</h6>
              <div style="height:130px;position:relative;"><canvas id="chart-acs-wifi"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 1: Device List -->
      <div id="acs-sec-dev" class="acs-sec" style="display:none;">
        <div style="display:flex;gap:8px;margin-bottom:10px;align-items:center;">
          <div style="flex:1;position:relative;">
            <input id="acs-search-input" type="text" placeholder="Cari Serial, IP, PPPoE User, atau Model..." style="width:100%;padding:6px 10px;font-size:11px;border-radius:6px;border:1px solid #cbd5e1;outline:none;" oninput="filterAcsTable(this.value)">
          </div>
          <button class="btn-p" style="font-size:11px;padding:6px 12px;background:#2563eb;" onclick="loadAcsDevices()"><i class="bx bx-refresh"></i> Refresh List</button>
          <button class="btn-p" style="font-size:11px;padding:6px 12px;background:#16a34a;" onclick="syncGenieACS()"><i class="bx bx-sync"></i> Sync Ke Database</button>
        </div>
        <div class="tbl-wrap" style="max-height:360px;">
          <table id="data-table" style="width:100%;">
            <thead>
              <tr>
                <th>Serial Number</th><th>Vendor / Model</th><th>IP / PPPoE User</th>
                <th>Redaman RX</th><th>SSID WiFi</th><th>Status Inform</th><th>Aksi TR-069</th>
              </tr>
            </thead>
            <tbody id="acs-dev-tbody">
              <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--muted);">Klik Refresh List untuk memuat perangkat GenieACS...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 2: Device Detail & Remote Controls -->
      <div id="acs-sec-det" class="acs-sec" style="display:none;">
        <div id="acs-det-empty" style="text-align:center;padding:30px;color:var(--muted);font-size:12px;">
          <i class="bx bx-info-circle" style="font-size:24px;display:block;margin-bottom:6px;color:var(--acc);"></i>
          Pilih salah satu device dari tab <b>Daftar CPE Devices</b> untuk melihat telemetri, redaman, pengguna terhubung, dan pengaturan remote TR-069.
        </div>
        <div id="acs-det-content" style="display:none;">
          <!-- Device Info Header -->
          <div style="background:#f8fafc;border:1px solid var(--bd);border-radius:8px;padding:10px 12px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
            <div>
              <h6 style="font-size:13px;font-weight:700;color:#0f172a;margin:0;" id="det-serial-title">Serial: —</h6>
              <div style="font-size:10px;color:var(--muted);margin-top:2px;" id="det-vendor-sub">Model: — | FW: —</div>
            </div>
            <div style="display:flex;gap:6px;">
              <button class="ping-btn" onclick="refreshCurrentAcsDevice()"><i class="bx bx-refresh"></i> Refresh TR-069</button>
              <button class="p-wa rbt" style="padding:4px 8px;font-size:10px;" onclick="rebootCurrentAcsDevice()"><i class="bx bx-power-off"></i> Reboot</button>
              <button class="p-wa rbt" style="padding:4px 8px;font-size:10px;background:#fee2e2;color:#b91c1c;" onclick="factoryResetCurrentAcsDevice()"><i class="bx bx-reset"></i> Reset Factory</button>
            </div>
          </div>

          <!-- Grid: Wi-Fi Config & Telemetry -->
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:14px;margin-bottom:14px;">
            <!-- WiFi Management Card -->
            <div style="background:#ffffff;border:1px solid var(--bd);border-radius:8px;padding:12px;">
              <div style="font-size:11px;font-weight:700;color:#0f172a;margin-bottom:8px;display:flex;align-items:center;gap:4px;">
                <i class="bx bx-wifi" style="color:var(--acc);"></i> Remote Pengaturan Wi-Fi
              </div>
              <div class="fg">
                <label>Nama Wi-Fi (SSID)</label>
                <input id="det-wifi-ssid" type="text" placeholder="SSID WiFi...">
              </div>
              <div class="fg">
                <label>Password Wi-Fi (PreSharedKey)</label>
                <input id="det-wifi-pass" type="text" placeholder="Password WiFi baru (min 8 karakter)...">
              </div>
              <button class="btn-p" style="width:100%;justify-content:center;font-size:11px;" onclick="saveCurrentAcsWifi()"><i class="bx bx-save"></i> Simpan WiFi via TR-069</button>
            </div>

            <!-- PPPoE WAN & SFP Signal Card -->
            <div style="background:#ffffff;border:1px solid var(--bd);border-radius:8px;padding:12px;">
              <div style="font-size:11px;font-weight:700;color:#0f172a;margin-bottom:8px;display:flex;align-items:center;gap:4px;">
                <i class="bx bx-network-chart" style="color:var(--acc);"></i> Status WAN & Optik SFP
              </div>
              <div class="calc-row"><span>Redaman Optik (RX Power)</span><span id="det-rx-val" style="font-weight:700;">—</span></div>
              <div class="calc-row"><span>External WAN IP</span><code id="det-ip-val" style="font-size:10px;">—</code></div>
              <div class="calc-row"><span>Uptime Device</span><span id="det-uptime-val">—</span></div>
              <div class="calc-row"><span>Inform Terakhir</span><span id="det-inform-val">—</span></div>

              <div class="p-divider"></div>
              <div class="fg" style="margin-bottom:6px;">
                <label>PPPoE Username</label>
                <input id="det-pppoe-user" type="text" placeholder="username@isp...">
              </div>
              <div class="fg" style="margin-bottom:6px;">
                <label>PPPoE Password</label>
                <input id="det-pppoe-pass" type="text" placeholder="password...">
              </div>
              <button class="btn-p" style="width:100%;justify-content:center;font-size:11px;background:#7c3aed;" onclick="saveCurrentAcsPppoe()"><i class="bx bx-save"></i> Update PPPoE WAN</button>
            </div>
          </div>

          <!-- Connected Clients / Pengguna Terhubung -->
          <div style="background:#ffffff;border:1px solid var(--bd);border-radius:8px;padding:12px;">
            <div style="font-size:11px;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;">
              <span><i class="bx bx-devices" style="color:var(--acc);"></i> Pengguna & Client Terhubung (<span id="det-cli-cnt">0</span>)</span>
            </div>
            <div class="tbl-wrap" style="max-height:160px;">
              <table style="width:100%;font-size:10px;">
                <thead>
                  <tr><th>Host / Nama</th><th>IP Address</th><th>MAC Address</th><th>Tipe Koneksi</th></tr>
                </thead>
                <tbody id="det-cli-tbody">
                  <tr><td colspan="4" style="text-align:center;padding:10px;color:var(--muted);">Tidak ada client terdeteksi.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- All Parameters Tree Viewer (Matching GenieACS Native UI) -->
          <div style="background:#ffffff;border:1px solid var(--bd);border-radius:8px;padding:12px;margin-top:12px;">
            <div style="font-size:11px;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;">
              <span><i class="bx bx-code-block" style="color:var(--acc);"></i> All Parameters Tree (CWMP Explorer)</span>
              <input type="text" id="acs-param-search" placeholder="Search parameters..." style="font-size:10px;padding:3px 8px;border-radius:4px;border:1px solid #cbd5e1;" oninput="filterAcsParams(this.value)">
            </div>
            <div style="max-height:180px;overflow-y:auto;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:8px;font-family:monospace;font-size:10px;color:#334155;" id="acs-param-tree-box">
              <div>DeviceID.ID</div>
              <div>DeviceID.Manufacturer</div>
              <div>DeviceID.OUI</div>
              <div>DeviceID.ProductClass</div>
              <div>DeviceID.SerialNumber</div>
              <div>Events.0_BOOTSTRAP</div>
              <div>Events.1_BOOT</div>
              <div>Events.Inform</div>
              <div>Events.Registered</div>
            </div>
          </div>
        </div>
      </div>
      <!-- Tab 3: Presets -->
      <div id="acs-sec-pst" class="acs-sec" style="display:none;">
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;align-items:center;">
          <h6 style="font-size:12px;font-weight:700;margin:0;"><i class="bx bx-slider-alt" style="color:var(--acc);"></i> Presets & Provisioning Rules</h6>
          <button class="btn-p" style="font-size:10px;padding:4px 8px;" onclick="loadAcsPresets()"><i class="bx bx-refresh"></i> Refresh Presets</button>
        </div>
        <div class="tbl-wrap" style="max-height:300px;">
          <table style="width:100%;font-size:11px;">
            <thead><tr><th>Preset ID / Name</th><th>Weight</th><th>Precondition</th><th>Events</th></tr></thead>
            <tbody id="acs-pst-tbody">
              <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">Klik Refresh untuk memuat daftar Presets...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 4: Provisions -->
      <div id="acs-sec-prv" class="acs-sec" style="display:none;">
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;align-items:center;">
          <h6 style="font-size:12px;font-weight:700;margin:0;"><i class="bx bx-code-alt" style="color:var(--acc);"></i> Provisioning Scripts</h6>
          <button class="btn-p" style="font-size:10px;padding:4px 8px;" onclick="loadAcsProvisions()"><i class="bx bx-refresh"></i> Refresh Scripts</button>
        </div>
        <div class="tbl-wrap" style="max-height:300px;">
          <table style="width:100%;font-size:11px;">
            <thead><tr><th>Provision ID</th><th>Script Name</th></tr></thead>
            <tbody id="acs-prv-tbody">
              <tr><td colspan="2" style="text-align:center;padding:20px;color:var(--muted);">Klik Refresh untuk memuat daftar Provisions...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 5: Faults Log -->
      <div id="acs-sec-flt" class="acs-sec" style="display:none;">
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;align-items:center;">
          <h6 style="font-size:12px;font-weight:700;margin:0;"><i class="bx bx-error-circle" style="color:var(--red);"></i> TR-069 Faults / Errors Log</h6>
          <button class="btn-p" style="font-size:10px;padding:4px 8px;" onclick="loadAcsFaults()"><i class="bx bx-refresh"></i> Refresh Faults</button>
        </div>
        <div class="tbl-wrap" style="max-height:300px;">
          <table style="width:100%;font-size:11px;">
            <thead><tr><th>Device Serial</th><th>Code</th><th>Message</th><th>Timestamp</th></tr></thead>
            <tbody id="acs-flt-tbody">
              <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">Klik Refresh untuk memuat log Faults...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 6: Files FS (Firmware & Configuration Storage) -->
      <div id="acs-sec-fls" class="acs-sec" style="display:none;">
        <div style="background:#f8fafc;border:1px solid var(--bd);border-radius:8px;padding:12px;margin-bottom:12px;">
          <div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
            <i class="bx bx-cloud-upload" style="color:var(--acc);font-size:16px;"></i> Unggah File Firmware / Config Baru ke GenieACS FS
          </div>
          <form id="form-upload-acs-file" onsubmit="submitAcsFileUpload(event)" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)) auto;gap:8px;align-items:end;">
            <div class="fg" style="margin:0;">
              <label style="font-size:10px;">Pilih File (Firmware / XML Config)</label>
              <input type="file" id="acs-file-input" required style="padding:4px;font-size:11px;">
            </div>
            <div class="fg" style="margin:0;">
              <label style="font-size:10px;">Tipe File</label>
              <select id="acs-file-type" style="padding:5px;font-size:11px;">
                <option value="1 Firmware Upgrade Image">1 Firmware Upgrade Image</option>
                <option value="3 Vendor Configuration File">3 Vendor Configuration File</option>
                <option value="X_CUSTOM_ConfigFile">Custom Configuration File</option>
              </select>
            </div>
            <div class="fg" style="margin:0;">
              <label style="font-size:10px;">Versi (Opsional)</label>
              <input type="text" id="acs-file-ver" placeholder="v1.0.0" style="padding:5px;font-size:11px;">
            </div>
            <button type="submit" class="btn-p" style="font-size:11px;padding:7px 14px;background:#16a34a;height:32px;">
              <i class="bx bx-upload"></i> Unggah File
            </button>
          </form>
        </div>

        <div style="display:flex;justify-content:space-between;margin-bottom:10px;align-items:center;">
          <h6 style="font-size:12px;font-weight:700;margin:0;"><i class="bx bx-folder" style="color:var(--acc);"></i> Daftar File Terdaftar di GenieACS FS</h6>
          <button class="btn-p" style="font-size:10px;padding:4px 8px;" onclick="loadAcsFiles()"><i class="bx bx-refresh"></i> Refresh Files</button>
        </div>
        <div class="tbl-wrap" style="max-height:300px;">
          <table style="width:100%;font-size:11px;">
            <thead><tr><th>File Name</th><th>FileType</th><th>Version</th><th style="width:70px;text-align:center;">Aksi</th></tr></thead>
            <tbody id="acs-fls-tbody">
              <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">Klik Refresh untuk memuat daftar Files...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 7: Server Config NBI -->
      <div id="acs-sec-cfg" class="acs-sec" style="display:none;">
        <div style="background:#f8fafc;border:1px solid var(--bd);border-radius:8px;padding:14px;max-width:500px;margin:0 auto;">
          <div class="fg">
            <label>GenieACS NBI Server URL</label>
            <input id="acs-nbi-url" type="text" value="http://localhost:7557" placeholder="http://localhost:7557">
          </div>
          <div class="form-row">
            <div class="fg">
              <label>NBI Username (Opsional)</label>
              <input id="acs-nbi-user" type="text" value="admin" placeholder="admin">
            </div>
            <div class="fg">
              <label>NBI Password (Opsional)</label>
              <input id="acs-nbi-pass" type="password" placeholder="Password...">
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-top:10px;">
            <button class="btn-p" style="flex:1;justify-content:center;" onclick="testAcsServerConnection()"><i class="bx bx-pulse"></i> Test NBI Connection</button>
            <button class="btn-p" style="flex:1;justify-content:center;background:#16a34a;" onclick="saveAcsUrl()"><i class="bx bx-save"></i> Simpan Config</button>
          </div>
        </div>
      </div>

    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-acs-config')">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Perangkat -->
<div class="modal-backdrop" id="m-perangkat">
  <div class="modal">
    <div class="m-hdr"><h5><i class="bx bx-devices"></i> Manajemen Perangkat FTTH</h5><button class="m-close" onclick="closeModal('m-perangkat')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;">
        <button class="btn-p" onclick="openAddPanel('olt');closeModal('m-perangkat');" style="padding:14px 8px;justify-content:center;flex-direction:column;gap:6px;"><i class="bx bx-server" style="font-size:22px;"></i> Tambah OLT</button>
        <button class="btn-p" style="background:#f97316;padding:14px 8px;justify-content:center;flex-direction:column;gap:6px;" onclick="openAddPanel('odc');closeModal('m-perangkat');"><i class="bx bx-cube-alt" style="font-size:22px;"></i> Tambah ODC</button>
        <button class="btn-p" style="background:#eab308;padding:14px 8px;justify-content:center;flex-direction:column;gap:6px;" onclick="openAddPanel('odp');closeModal('m-perangkat');"><i class="bx bx-box" style="font-size:22px;"></i> Tambah ODP</button>
        <button class="btn-p" style="background:#16a34a;padding:14px 8px;justify-content:center;flex-direction:column;gap:6px;" onclick="openAddPanel('ont');closeModal('m-perangkat');"><i class="bx bx-home-alt" style="font-size:22px;"></i> Tambah ONT</button>
      </div>
    </div>
    <div class="m-foot"><button class="btn-s" onclick="closeModal('m-perangkat')">Tutup</button></div>
  </div>
</div>

<!-- Auto Generate Tiang Modal -->
<div class="modal-backdrop" id="m-auto-tiang">
  <div class="modal" style="max-width:440px;">
    <div class="m-hdr">
      <h5><i class="bx bx-map-pin"></i> Auto Generate Tiang Tumpu</h5>
      <button class="m-close" onclick="closeModal('m-auto-tiang')"><i class="bx bx-x"></i></button>
    </div>
    <div class="m-body">
      <div class="fg">
        <label>Pilih Jalur Kabel</label>
        <select id="at-kabel-select"></select>
      </div>
      <div class="fg">
        <label>Jarak Antar Tiang (Meter)</label>
        <input id="at-jarak" type="number" value="50" min="10" max="500" oninput="document.getElementById('at-jarak-lbl').textContent = this.value">
      </div>
      <div style="font-size:10px;color:var(--muted);background:#f8fafc;border:1px solid var(--bd);padding:10px;border-radius:6px;line-height:1.4;">
        <i class="bx bx-info-circle" style="color:var(--acc);"></i> Fitur ini secara otomatis menghitung koordinat sepanjang jalur kabel dan menambahkan marker <b>Tiang Tumpu</b> setiap <b id="at-jarak-lbl">50</b> meter ke database & peta.
      </div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-auto-tiang')">Batal</button>
      <button class="btn-p" onclick="submitAutoGenerateTiang()"><i class="bx bx-bolt"></i> Generate Tiang</button>
    </div>
  </div>
</div>

<!-- ONT Detail (popup extra) -->
<div class="modal-backdrop" id="m-wifi">
  <div class="modal" style="max-width:360px;">
    <div class="m-hdr"><h5 id="wifi-modal-title"><i class="bx bx-wifi"></i> Ganti WiFi</h5><button class="m-close" onclick="closeModal('m-wifi')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div class="fg"><label>SSID (Nama WiFi)</label><input id="wifi-ssid" type="text" placeholder="Nama WiFi baru..."></div>
      <div class="fg"><label>Password</label><input id="wifi-pass" type="text" placeholder="Password baru (min 8 karakter)..."></div>
      <div id="wifi-status-msg" style="font-size:11px;color:var(--muted);margin-top:8px;"></div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-wifi')">Batal</button>
      <button class="btn-p" onclick="submitChangeWifi()"><i class="bx bx-sync"></i> Ganti WiFi</button>
    </div>
  </div>
</div>

<!-- Kalkulator Redaman -->
<div class="modal-backdrop" id="m-kalkulator">
  <div class="modal">
    <div class="m-hdr"><h5><i class="bx bx-calculator"></i> Kalkulator Redaman Optik</h5><button class="m-close" onclick="closeModal('m-kalkulator')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div class="form-row">
        <div class="fg"><label>Panjang Kabel (meter)</label><input id="k-panjang" type="number" placeholder="1000" value="1000"></div>
        <div class="fg"><label>Rasio Splitter</label>
          <select id="k-splitter">
            <option value="2">1:2 (−3.5 dB)</option>
            <option value="4">1:4 (−7.0 dB)</option>
            <option value="8" selected>1:8 (−10.5 dB)</option>
            <option value="16">1:16 (−14.0 dB)</option>
            <option value="32">1:32 (−17.5 dB)</option>
            <option value="64">1:64 (−21.0 dB)</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Jumlah Splitter</label><input id="k-jml-splitter" type="number" value="2" min="1"></div>
        <div class="fg"><label>TX Power OLT (dBm)</label><input id="k-tx" type="number" value="2" step="0.5"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Jumlah Konektor</label><input id="k-kon" type="number" value="4" min="0"></div>
        <div class="fg"><label>Jumlah Splice</label><input id="k-splice" type="number" value="4" min="0"></div>
      </div>
      <button class="btn-p" style="width:100%;margin-top:4px;" onclick="hitungRedaman()"><i class="bx bx-bolt"></i> Hitung Redaman</button>
      <div id="kalk-result" class="calc-result" style="display:none;"></div>
    </div>
  </div>
</div>

<!-- Ping Terminal -->
<div class="modal-backdrop" id="m-ping">
  <div class="modal term">
    <div class="m-hdr">
      <h5><i class="bx bx-terminal"></i> Ping Terminal</h5>
      <button class="m-close" onclick="closeModal('m-ping')"><i class="bx bx-x"></i></button>
    </div>
    <div class="m-body">
      <div style="display:flex;gap:8px;margin-bottom:10px;">
        <input id="ping-ip" type="text" style="flex:1;background:#f8fafc;border:1px solid #cbd5e1;
               border-radius:6px;color:#0f172a;font-family:monospace;font-size:12px;
               padding:7px 10px;outline:none;" placeholder="192.168.1.1">
        <button class="btn-p" id="btn-do-ping" onclick="doPing()"><i class="bx bx-play"></i> Ping</button>
        <button class="btn-s" onclick="clearTerminal()">Bersihkan</button>
      </div>
      <div id="terminal-out"><!-- output --></div>
    </div>
  </div>
</div>

<!-- Tabel ONU -->
<div class="modal-backdrop" id="m-tabel">
  <div class="modal wide">
    <div class="m-hdr">
      <h5><i class="bx bx-table"></i> Tabel Data ONU / Pelanggan</h5>
      <div style="display:flex;gap:6px;align-items:center;">
        <input id="tbl-search" type="text" style="background:#ffffff;border:1px solid #cbd5e1;
               border-radius:6px;color:#0f172a;font-size:11px;padding:5px 10px;outline:none;
               font-family:inherit;" placeholder="Filter...">
        <button class="btn-p" onclick="exportCsv()" style="font-size:11px;padding:5px 12px;"><i class="bx bx-download"></i> CSV</button>
        <button class="m-close" onclick="closeModal('m-tabel')"><i class="bx bx-x"></i></button>
      </div>
    </div>
    <div class="m-body" style="padding:12px;">
      <div class="tbl-wrap">
        <table id="data-table">
          <thead>
            <tr>
              <th>Kode</th><th>Nama</th><th>IP</th>
              <th>ODP</th><th>ODC</th><th>OLT</th>
              <th>Status</th><th>Serial ONT</th><th>RX Power</th>
              <th>Koordinat</th>
            </tr>
          </thead>
          <tbody id="tbl-body"></tbody>
        </table>
      </div>
      <div id="tbl-info" style="font-size:10px;color:var(--muted);margin-top:8px;"></div>
    </div>
  </div>
</div>

<!-- Telegram Settings -->
<div class="modal-backdrop" id="m-telegram">
  <div class="modal">
    <div class="m-hdr"><h5><i class="bx bxl-telegram"></i> Pengaturan Notifikasi Telegram</h5><button class="m-close" onclick="closeModal('m-telegram')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div class="fg"><label>Bot Token</label><input id="tg-token" type="text" placeholder="1234567890:ABCdef..."></div>
      <div class="fg"><label>Chat ID / Group ID</label><input id="tg-chatid" type="text" placeholder="-100123456789"></div>
      <div style="display:flex;flex-direction:column;gap:8px;font-size:12px;margin-bottom:12px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input id="tg-en" type="checkbox"> <span>Aktifkan Notifikasi Telegram</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input id="tg-onu" type="checkbox"> <span>ONU Offline Alert</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input id="tg-odp" type="checkbox"> <span>ODP Penuh Alert</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input id="tg-kbl" type="checkbox"> <span>Kabel Offline Alert</span>
        </label>
      </div>
      <div class="fg"><label>Threshold Offline (menit)</label>
        <input id="tg-thresh" type="number" value="5" min="1">
      </div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="testTelegram()">Test Kirim</button>
      <button class="btn-p" onclick="saveTelegramSettings()">Simpan</button>
    </div>
  </div>
</div>

<!-- Backup/Restore Modal -->
<div class="modal-backdrop" id="m-backup">
  <div class="modal">
    <div class="m-hdr"><h5><i class="bx bx-data"></i> Backup & Restore Data</h5><button class="m-close" onclick="closeModal('m-backup')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
        <button class="btn-p" onclick="exportCsv()" style="padding:12px;justify-content:center;"><i class="bx bx-download"></i> Export CSV<br><small style="font-weight:400;font-size:10px;">Semua data pelanggan & node</small></button>
        <button class="btn-p" style="padding:12px;background:#15803d;justify-content:center;" onclick="window.open(`${BASE}/ftth/export-kmz`,'_blank')"><i class="bx bx-export"></i> Export KMZ<br><small style="font-weight:400;font-size:10px;">Jalur kabel & marker Google Earth</small></button>
      </div>
      <div class="fg"><label>Import File (CSV / KMZ / KML)</label>
        <input type="file" id="import-file" accept=".csv,.kmz,.kml" style="font-size:11px;color:var(--text);">
      </div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-backup')">Tutup</button>
      <button class="btn-p" onclick="submitImportFile()"><i class="bx bx-upload"></i> Proses Import</button>
    </div>
  </div>
</div>

<!-- Notifikasi Modal -->
<div class="modal-backdrop" id="m-notif">
  <div class="modal">
    <div class="m-hdr"><h5><i class="bx bx-bell"></i> Center Notifikasi FTTH</h5><button class="m-close" onclick="closeModal('m-notif')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div id="notif-modal-list" style="font-size:11px;">
        <div style="padding:10px;background:#fee2e2;border-radius:6px;margin-bottom:6px;border-left:3px solid var(--red);">
          <strong style="color:var(--red);">Alert Offline</strong>
          <p style="color:#7f1d1d;margin-top:2px;" id="notif-off-text">Ada perangkat offline dalam jaringan.</p>
        </div>
      </div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-notif')">Tutup</button>
    </div>
  </div>
</div>

<!-- Edit Node Modal -->
<div class="modal-backdrop" id="m-edit-node">
  <div class="modal" style="max-width:440px;">
    <div class="m-hdr">
      <h5 id="en-title"><i class="bx bx-edit"></i> Edit Perangkat / Node</h5>
      <button class="m-close" onclick="closeModal('m-edit-node')"><i class="bx bx-x"></i></button>
    </div>
    <div class="m-body">
      <input type="hidden" id="en-id">
      <input type="hidden" id="en-type">
      <div class="fg">
        <label>Nama Perangkat / Node</label>
        <input id="en-nama" type="text" placeholder="odc-c320-c1. atau odp-c320-c1-c1">
      </div>
      <div class="form-row">
        <div class="fg">
          <label>Latitude</label>
          <input id="en-lat" type="number" step="any">
        </div>
        <div class="fg">
          <label>Longitude</label>
          <input id="en-lng" type="number" step="any">
        </div>
      </div>
      <div class="fg">
        <label><i class="bx bx-purchase-tag"></i> Label / Catatan Keterangan Alat</label>
        <textarea id="en-catatan" rows="2" placeholder="Tuliskan keterangan detail alat/tiang/lokasi..."></textarea>
      </div>
      <div id="en-extra-fg"></div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-edit-node')">Batal</button>
      <button class="btn-p" onclick="submitUpdateNode()"><i class="bx bx-save"></i> Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- Queue Modal -->
<div class="modal-backdrop" id="m-queue">
  <div class="modal" style="max-width:420px;">
    <div class="m-hdr"><h5><i class="bx bx-list-ol"></i> Background Jobs & Queue</h5><button class="m-close" onclick="closeModal('m-queue')"><i class="bx bx-x"></i></button></div>
    <div class="m-body">
      <div style="font-size:12px;color:var(--muted);margin-bottom:8px;">Status antrian polling FTTH:</div>
      <div style="background:#f8fafc;border:1px solid var(--bd);border-radius:6px;padding:10px;font-size:11px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Scheduler Poll Status</span><span style="color:var(--green);font-weight:600;">Active (5m)</span></div>
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>AcsSyncJob</span><span style="color:var(--green);font-weight:600;">Idle</span></div>
        <div style="display:flex;justify-content:space-between;"><span>RftsPollJob</span><span style="color:var(--green);font-weight:600;">Idle</span></div>
      </div>
    </div>
    <div class="m-foot">
      <button class="btn-s" onclick="closeModal('m-queue')">Tutup</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<!-- ══════════════════════════════════════ SCRIPTS ══════════════════ -->
<script src="{{ asset('libs/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('libs/leaflet-draw/leaflet.draw.min.js') }}"></script>
<script src="{{ asset('libs/leaflet-cluster/leaflet.markercluster.min.js') }}"></script>
<script src="{{ asset('libs/chart.min.js') }}"></script>

<script>
'use strict';
const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
const BASE = "{{ url('/') }}".replace(/\/$/, "");

// ──────────────── MODALS HELPERS (GLOBAL TOP LEVEL) ──────────────────
function openModal(id) {
  var el = document.getElementById(id);
  if (el) {
    el.classList.add('open');
    if (id === 'm-simpan-kabel') {
      var pts = window.pendingCablePts || [];
      var startPt = pts.length ? pts[0] : null;
      var endPt = pts.length ? pts[pts.length - 1] : null;
      if (typeof populateCableNodeSelects === 'function') {
        populateCableNodeSelects(startPt, endPt);
      }
      if (typeof onCableTypeSelectChange === 'function') {
        onCableTypeSelectChange();
      }
    }
  } else {
    console.warn('Modal not found:', id);
  }
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) {
    el.classList.remove('open');
  }
}

document.addEventListener('click', function(e) {
  if (e.target && e.target.classList && e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
  }
});

// ──────────────── MAP SETUP ─────────────────────────────────────────
var MAP, darkTile, satTile, osmTile, currentTile;
var L_OLT   = L.layerGroup();
var L_ODC   = L.layerGroup();
var L_ODP   = L.layerGroup();
var L_CABLE = L.layerGroup();
var L_ONT   = L.markerClusterGroup({
  maxClusterRadius:45,disableClusteringAtZoom:17,
  iconCreateFunction: c => L.divIcon({
    className:'',iconSize:[32,32],
    html:`<div class="lmc">${c.getChildCount()}</div>`,
  })
});
var L_ITEM  = L.layerGroup();
var L_LABEL = L.layerGroup();

var showLabels = true, animEnabled = true;
var DATA = { olts:[], odcOdps:[], pelanggan:[], kabels:[], items:[] };
var markerReg = {}, kabelReg = {}, labelReg = {};
var rpSort = 'terlama';
var drawActive = false, drawPts = [], drawTmpLine = null, drawTmpMarkers = [];
var activeWifiPelanggan = null, activePopupPelanggan = null;
var trafficCharts = {};
var autoRefreshTimer;

// ──────────────── INIT ──────────────────────────────────────────────
function initMap() {
  try {
    MAP = L.map('ftth-map',{center:[-7.1207,112.5959],zoom:14,zoomControl:false,attributionControl:false});

    satTile   = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',{attribution:'© Google Earth Satellite',maxZoom:20});
    darkTile  = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© CartoDB',maxZoom:19,subdomains:['a','b','c','d']});
    osmTile   = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19,subdomains:['a','b','c']});

    currentTile = satTile; // Default to Google Earth Satellite tile!
    satTile.addTo(MAP);

    [L_CABLE,L_LABEL,L_ITEM,L_ODP,L_ODC,L_OLT,L_ONT].forEach(l => l.addTo(MAP));
    L.control.zoom({position:'bottomright'}).addTo(MAP);

    MAP.on('mousemove', e => {
      var c = document.getElementById('st-coords');
      if (c && e.latlng) c.textContent = `Lat: ${e.latlng.lat.toFixed(6)}, Lng: ${e.latlng.lng.toFixed(6)}`;
    });
    MAP.on('click', e => { if (drawActive && e.latlng) addDrawPt(e.latlng); });
    MAP.on('dblclick', e => { if (drawActive) { e.originalEvent.preventDefault(); finishDraw(); } });

    loadAll();
    autoRefreshTimer = setInterval(() => loadAll(true), 60000);
    makeLegendDraggable();

    setTimeout(() => {
      var loader = document.getElementById('app-loader');
      if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 250);
      }
    }, 800);
  } catch(e) {
    console.error('Init map error:', e);
    var loader = document.getElementById('app-loader');
    if (loader) loader.style.display = 'none';
  }
}

// ──────────────── DATA LOAD (OPTIMIZED SINGLE API PAYLOAD) ──────────
async function loadAll(silent = false) {
  if (!silent) showToast('Memuat data jaringan...','ok');
  try {
    const nodesRes = await fetch(`${BASE}/ftth/api/nodes`, {headers:{Accept:'application/json'}});
    if (!nodesRes.ok) throw new Error(`HTTP ${nodesRes.status}`);
    const nodes = await nodesRes.json();

    DATA.olts     = (nodes.olts     || []).map(normalizeNode);
    DATA.odcOdps  = (nodes.odcOdps  || []).map(normalizeNode);
    DATA.pelanggan= (nodes.pelanggan|| []).map(normalizeNode);
    DATA.kabels   = (nodes.kabels   || []);
    DATA.items    = (nodes.ftthItems|| []).map(normalizeNode);

    renderAll(silent);
    renderSidebar();
    renderOfflinePanel();
    updateStatusBar();

    var loader = document.getElementById('app-loader');
    if (loader) {
      loader.style.opacity = '0';
      setTimeout(() => loader.style.display = 'none', 250);
    }

    if (!silent) showToast(`Data dimuat: ${DATA.pelanggan.length} ONT, ${DATA.kabels.length} kabel`,'ok');
  } catch(e) {
    console.error('Load Error:', e);
    if (!silent) showToast('Gagal memuat data: ' + e.message,'er');
    var loader = document.getElementById('app-loader');
    if (loader) loader.style.display = 'none';
  }
}

function normalizeNode(n) {
  if (!n) return {};
  if (n.status === null || n.status === undefined || typeof n.status !== 'string') {
    n.status = 'online';
  }
  if (n.tipe && typeof n.tipe !== 'string') n.tipe = String(n.tipe);
  if (n.nama && typeof n.nama !== 'string') n.nama = String(n.nama);
  return n;
}

// ──────────────── RENDER ─────────────────────────────────────────────
function renderAll(silent = false) {
  try {
    [L_OLT,L_ODC,L_ODP,L_ONT,L_CABLE,L_LABEL,L_ITEM].forEach(l => l.clearLayers());
    markerReg = {}; kabelReg = {}; labelReg = {};
    DATA.olts.forEach(renderOlt);
    DATA.odcOdps.forEach(n => (n.tipe||'').toUpperCase() === 'ODC' ? renderOdc(n) : renderOdp(n));
    DATA.pelanggan.forEach(renderOnt);
    DATA.kabels.forEach(renderKabel);
    DATA.items.forEach(renderItem);
    
    if (!silent) {
      fitAll(true);
    }
  } catch(e) {
    console.error('Render all error:', e);
  }
}

function colorOf(status) {
  status = String(status || 'online');
  return {online:'#16a34a',warning:'#d97706',offline:'#dc2626'}[status] || '#64748b';
}
function wtOf(tipe) { return {feeder:6,distribusi:4,drop:2}[String(tipe||'distribusi')] || 4; }

function makeIcon(boxClass, iconClass, size) {
  size = size || 34;
  return L.divIcon({
    className:'',
    html:`<div class="gis-marker ${boxClass}" style="width:${size}px;height:${size}px;">
            <i class="${iconClass}"></i>
          </div>`,
    iconSize:[size,size],iconAnchor:[size/2,size/2],popupAnchor:[0,-size/2+2],
  });
}
function handleMarkerClick(type, id, latlng, defaultFn, e) {
  if (drawActive) {
    if (e && e.originalEvent && e.originalEvent.stopPropagation) {
      e.originalEvent.stopPropagation();
    }
    var nodeNama = '';
    var all = [...(DATA.olts||[]), ...(DATA.odcOdps||[]), ...(DATA.pelanggan||[]), ...(DATA.ftthItems||[])];
    var matched = all.find(n => n.id == id);
    if (matched) nodeNama = matched.nama || matched.kode || '';
    if (nodeNama) showToast(`Titik terhubung ke node: ${nodeNama}`, 'ok');
    addDrawPt(latlng);
    return;
  }
  if (typeof defaultFn === 'function') defaultFn();
}

// ODP YELLOW BOX MARKER WITH NAME PILL
function makeOdpIcon(status, pelCount, maxPort, name, desc) {
  var stClass = status === 'offline' ? 'off' : status === 'warning' ? 'wa' : '';
  var txt = maxPort ? `${pelCount}/${maxPort}` : String(pelCount);
  var descHtml = desc ? `<span class="sub-desc">${desc}</span>` : '';
  return L.divIcon({
    className:'',
    html:`<div style="display:flex;flex-direction:column;align-items:center;">
            <div class="odp-yellow-box ${stClass}">
              <i class="bx bx-git-repo-forked" style="font-size:16px;"></i>
              <span class="odp-num">${txt}</span>
            </div>
            ${name ? `<div class="node-label-pill">${name}${descHtml}</div>` : ''}
          </div>`,
    iconSize:[70, desc ? 56 : 48],iconAnchor:[35,18],popupAnchor:[0,-20],
  });
}

// ODC ORANGE BOX MARKER WITH NAME PILL
function makeOdcIcon(status, core, name, desc) {
  var descHtml = desc ? `<span class="sub-desc">${desc}</span>` : '';
  return L.divIcon({
    className:'',
    html:`<div style="display:flex;flex-direction:column;align-items:center;">
            <div class="odc-box-marker">
              <i class="bx bx-cube-alt" style="font-size:18px;"></i>
            </div>
            ${name ? `<div class="node-label-pill">${name}${descHtml}</div>` : ''}
          </div>`,
    iconSize:[70, desc ? 56 : 48],iconAnchor:[35,18],popupAnchor:[0,-20],
  });
}

function renderOlt(n) {
  if (!n.lat || !n.lng) return;
  var desc = n.deskripsi || n.lokasi || n.catatan || '';
  var m = L.marker([n.lat,n.lng],{
    icon: L.divIcon({
      className:'',
      html:`<div style="display:flex;flex-direction:column;align-items:center;">
              <div class="gis-marker olt" style="width:34px;height:34px;"><i class="bx bx-server"></i></div>
              <div class="node-label-pill">${n.nama||'OLT'}${desc ? `<span class="sub-desc">${desc}</span>` : ''}</div>
            </div>`,
      iconSize:[80, desc ? 58 : 50],iconAnchor:[40,19],popupAnchor:[0,-22]
    }),
    draggable:true
  });
  m.bindPopup(buildNodePopup(n,'OLT',`<div class="p-row"><span class="lbl"><i class="bx bx-chip"></i> IP</span><span class="val mono">${n.ip_address||'—'}</span></div><div class="p-row"><span class="lbl"><i class="bx bx-broadcast"></i> PON</span><span class="val">${n.kapasitas_pon||'—'} port</span></div>`));
  m.on('dragend', e => savePos('olt',n.id,e.target.getLatLng()));
  m.on('click', (e) => handleMarkerClick('olt', n.id, m.getLatLng(), () => hiSidebar('olt',n.id), e));
  L_OLT.addLayer(m); markerReg['olt_'+n.id] = m;
}

function renderOdc(n) {
  if (!n.lat || !n.lng) return;
  var desc = n.deskripsi || n.catatan || n.lokasi || '';
  var m = L.marker([n.lat,n.lng],{icon:makeOdcIcon(n.status,n.kapasitas_core,n.nama,desc),draggable:true});
  m.bindPopup(buildNodePopup(n,'ODC',`<div class="p-row"><span class="lbl"><i class="bx bx-layer"></i> Kapasitas</span><span class="val">${n.kapasitas_core||'—'} core</span></div>`));
  m.on('dragend', e => savePos('odc',n.id,e.target.getLatLng()));
  m.on('click', (e) => handleMarkerClick('odc', n.id, m.getLatLng(), () => hiSidebar('odc',n.id), e));
  L_ODC.addLayer(m); markerReg['odc_'+n.id] = m;
}

function renderOdp(n) {
  if (!n.lat || !n.lng) return;
  var pelCount = DATA.pelanggan.filter(p => p.odp_id == n.id).length;
  var desc = n.deskripsi || n.catatan || n.lokasi || '';
  var m = L.marker([n.lat,n.lng],{icon:makeOdpIcon(n.status,pelCount,n.kapasitas_port,n.nama,desc),draggable:true});
  m.bindPopup(buildNodePopup(n,'ODP',
    `<div class="p-row"><span class="lbl"><i class="bx bx-plug"></i> Port</span><span class="val">${pelCount}/${n.kapasitas_port||'?'}</span></div>` +
    `<div class="p-row"><span class="lbl"><i class="bx bx-cube-alt"></i> ODC Induk</span><span class="val">${n.parent_id ? (DATA.odcOdps.find(x=>x.id===n.parent_id)?.nama||n.parent_id) : '—'}</span></div>`));
  m.on('dragend', e => savePos('odp',n.id,e.target.getLatLng()));
  m.on('click', (e) => handleMarkerClick('odp', n.id, m.getLatLng(), () => hiSidebar('odp',n.id), e));
  L_ODP.addLayer(m); markerReg['odp_'+n.id] = m;
}

function renderOnt(n) {
  if (!n.lat || !n.lng) return;
  var isOnline = n.status === 'online';
  var stClass = isOnline ? 'ont' : 'ont off';
  var desc = (n.alamat && n.alamat !== 'Lokasi Terdaftar FTTH Map') ? n.alamat : '';
  var descHtml = desc ? `<span class="sub-desc">${desc}</span>` : '';
  
  var m = L.marker([n.lat,n.lng],{
    icon:L.divIcon({
      className:'',
      html:`<div style="display:flex;flex-direction:column;align-items:center;">
              <div class="gis-marker ${stClass}" style="width:30px;height:30px;"><i class="bx bx-wifi"></i></div>
              <div class="node-label-pill">${n.nama||'ONT'}${descHtml}</div>
            </div>`,
      iconSize:[70, desc ? 50 : 42],iconAnchor:[35,15],popupAnchor:[0,-18]
    }),
    draggable:true
  });

  var rxClass = rxColorClass(n.onu_rx_power);
  var rxAwal = n.onu_rx_baseline ? `${n.onu_rx_baseline} dBm` : '—';
  var rxNow  = n.onu_rx_power  ? `${n.onu_rx_power} dBm` : '—';

  m.bindPopup(buildOntPopup(n, rxAwal, rxNow, rxClass), {maxWidth:290,minWidth:290});
  m.on('dragend', e => savePos('ont',n.id,e.target.getLatLng()));
  m.on('click', (e) => {
    if (drawActive) {
      if (e && e.originalEvent && e.originalEvent.stopPropagation) {
        e.originalEvent.stopPropagation();
      }
      setTimeout(() => m.closePopup(), 10);
      handleMarkerClick('pelanggan', n.id, m.getLatLng(), null, e);
      return;
    }
    hiSidebar('ont',n.id);
    activePopupPelanggan = n;
    setTimeout(() => initTrafficChart(n.id), 100);

    // Auto-fetch live SSID & Wi-Fi details from GenieACS
    fetch(`${BASE}/ftth/api/wifi-info/${n.id}`, {headers:{Accept:'application/json'}})
      .then(r => r.json())
      .then(d => {
        var isOnline = (n.status === 'online') && (d.is_online !== false);
        var ssidEl = document.getElementById('wifi-ssid-' + n.id);
        if (ssidEl) ssidEl.textContent = isOnline ? (d.ssid || 'Belum diset') : '—';
        
        var cliEl = document.getElementById('wifi-cli-' + n.id);
        if (cliEl) cliEl.textContent = isOnline ? (d.clients_count || '1 Online') : '0 (Offline)';
        
        var rxNowEl = document.getElementById('rx-now-' + n.id);
        if (rxNowEl) {
          if (isOnline && d.rx_power) {
            rxNowEl.textContent = d.rx_power + ' dBm';
            rxNowEl.className = 'r-val ' + rxColorClass(d.rx_power);
          } else if (!isOnline) {
            rxNowEl.textContent = 'Putus';
            rxNowEl.className = 'r-val r-kritis';
          }
        }

        var upEl = document.getElementById('acs-up-' + n.id);
        if (upEl) {
          var uptime = n.last_inform_at ? offDuration(n.last_inform_at, !isOnline) : '—';
          if (isOnline) {
            upEl.textContent = 'Aktif · Up: ' + (d.uptime && d.uptime !== '-' ? d.uptime : uptime);
            upEl.className = 'val on';
          } else {
            upEl.textContent = 'Offline · Mati: ' + uptime;
            upEl.className = 'val off';
          }
        }
      })
      .catch(() => {
        var ssidEl = document.getElementById('wifi-ssid-' + n.id);
        if (ssidEl) ssidEl.textContent = '—';
      });
  });
  L_ONT.addLayer(m);
  markerReg['pelanggan_'+n.id] = m;
}

// RENDER KABEL + CABLE NAME LABEL + DISTANCE PILLS (CLEAN & RAPI)
function renderKabel(k) {
  if (!k.geometry || k.geometry.length < 2) return;
  var colorMap = {
    feeder: '#0284c7',        // Cyan
    distribusi: '#d97706',    // Kuning/Orange
    backbone: '#8b5cf6',      // Ungu
    trunk: '#ec4899',         // Magenta
    sub_distribusi: '#10b981',// Hijau Emerald
    drop: '#2563eb'           // Biru
  };
  var color = (k.color && k.color !== '#28a745' && k.color !== '#dc3545') ? k.color : (colorMap[k.tipe] || '#0284c7');
  if (k.status === 'offline' || k.status === 'putus') {
    color = '#dc2626'; // Red if offline/broken
  }
  var line = L.polyline(k.geometry,{
    color, weight: wtOf(k.tipe), opacity:.95, smoothFactor:1.5,
    dashArray: (k.tipe === 'feeder' || k.tipe === 'backbone') ? '8, 6' : (k.tipe==='distribusi'||k.tipe==='trunk' ? '10, 5' : '6, 4')
  });
  line.bindPopup(buildKabelPopup(k));
  line.on('click', () => hiSidebar('kabel',k.id));
  L_CABLE.addLayer(line);
  kabelReg[k.id] = line;

  if (animEnabled && line._path) {
    line._path.classList.add('animated-cable');
  }

  // Hitung total meter rute kabel
  var totalMeters = 0;
  for (var i = 0; i < k.geometry.length - 1; i++) {
    var ptA = L.latLng(k.geometry[i][0], k.geometry[i][1]);
    var ptB = L.latLng(k.geometry[i+1][0], k.geometry[i+1][1]);
    totalMeters += Math.round(ptA.distanceTo(ptB));
  }

  // Main Cable Name Label Pill (Clean Structured 2-Line Enterprise Style)
  var midIndex = Math.floor(k.geometry.length / 2);
  var midPt = k.geometry[midIndex];
  var catTxt = k.catatan ? `<div class="sub-desc">${k.catatan}</div>` : '';
  var labelTooltip = L.tooltip({
    permanent: true, direction: 'top', className: 'cl', interactive: true
  }).setLatLng(midPt).setContent(`
    <div style="text-align:center;cursor:pointer;" onclick="var l=kabelReg[${k.id}];if(l)l.openPopup();">
      <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
        <span style="color:${color};font-weight:800;font-size:11px;">━</span>
        <b style="color:#0f172a;font-size:9.5px;">${k.label}</b>
        <span style="color:#64748b;font-size:8.5px;font-weight:500;">(${totalMeters}m)</span>
        <button onclick="event.stopPropagation();if(confirm('Hapus kabel ${k.label}?'))deleteKabel(${k.id})" style="background:none;border:none;color:#ef4444;padding:0 2px;cursor:pointer;font-size:12px;" title="Hapus Kabel"><i class="bx bx-trash"></i></button>
      </div>
      ${catTxt}
    </div>
  `);
  labelReg[`lbl_${k.id}`] = labelTooltip;
  if (showLabels) L_LABEL.addLayer(labelTooltip);

  // Segment Distance Pills: Tampilkan hanya jika jarak segmen cukup panjang (>= 35 meter) agar tidak menumpuk berantakan
  for (var i = 0; i < k.geometry.length - 1; i++) {
    var p1 = L.latLng(k.geometry[i][0], k.geometry[i][1]);
    var p2 = L.latLng(k.geometry[i+1][0], k.geometry[i+1][1]);
    var distMeters = Math.round(p1.distanceTo(p2));
    
    // Jangan buat pill untuk segmen tikungan kecil (< 35m) agar peta tetap bersih dan rapi
    if (distMeters < 35 && k.geometry.length > 3) continue;

    var midLat = (k.geometry[i][0] + k.geometry[i+1][0]) / 2;
    var midLng = (k.geometry[i][1] + k.geometry[i+1][1]) / 2;

    var distTooltip = L.tooltip({
      permanent: true, direction: 'center', className: 'cable-dist-pill', interactive: false
    }).setLatLng([midLat, midLng]).setContent(`${distMeters} m`);

    labelReg[`dist_${k.id}_${i}`] = distTooltip;
    if (showLabels) L_LABEL.addLayer(distTooltip);
  }

  if (k.status === 'offline' && k.titik_putus_meter) {
    L.marker(k.geometry[0],{
      icon:L.divIcon({className:'',html:'<div style="color:var(--red);font-size:18px;"><i class="bx bx-error-alt"></i></div>',iconSize:[22,22]})
    }).bindTooltip(`Putus ±${k.titik_putus_meter}m`).addTo(L_CABLE);
  }
}

var ITEM_COLORS = {
  tiang_tumpu:'#0f172a', tiang_loop:'#0f172a', slack_loop:'#0f172a',
  tiang_odp:'#2563eb', tiang_odc:'#7c3aed',
  joint_closure:'#ea580c', htb_ap:'#059669', server_router:'#d97706',
};

function makeItemIcon(kategori, status, name, desc) {
  var descHtml = desc ? `<span class="sub-desc">${desc}</span>` : '';
  var nameHtml = name ? `<div class="node-label-pill">${name}${descHtml}</div>` : '';
  
  var svgContent = '';
  var anchorY = 20;

  if (kategori === 'tiang_loop') {
    // Icon 1 (Sama persis Gambar Kiri): Circle + Hollow Horizontal Rect + Hollow Vertical Rect
    anchorY = 20;
    svgContent = `
      <svg width="30" height="40" viewBox="0 0 30 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="15" cy="15" r="11" stroke="#000000" stroke-width="2.5" fill="#ffffff"/>
        <rect x="2" y="12" width="26" height="6" stroke="#000000" stroke-width="2" fill="#ffffff"/>
        <rect x="12" y="2" width="6" height="36" stroke="#000000" stroke-width="2" fill="#ffffff"/>
      </svg>`;
  } else if (kategori === 'slack_loop' || kategori === 'joint_closure_oval') {
    // Icon 2 (Sama persis Gambar Tengah): Oval + Hollow Horizontal Bar + Hollow Vertical Bar
    anchorY = 15;
    svgContent = `
      <svg width="40" height="30" viewBox="0 0 40 30" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="20" cy="15" rx="17" ry="8" stroke="#000000" stroke-width="2.5" fill="#ffffff"/>
        <rect x="2" y="12" width="36" height="6" rx="2" stroke="#000000" stroke-width="2" fill="#ffffff"/>
        <rect x="16" y="3" width="8" height="24" rx="2" stroke="#000000" stroke-width="2" fill="#ffffff"/>
      </svg>`;
  } else if (kategori === 'tiang_tumpu') {
    // Icon 3 (Sama persis Gambar Kanan): Solid T-Bar Line Pole
    anchorY = 17;
    svgContent = `
      <svg width="24" height="34" viewBox="0 0 24 34" fill="none" xmlns="http://www.w3.org/2000/svg">
        <line x1="2" y1="10" x2="22" y2="10" stroke="#000000" stroke-width="3" stroke-linecap="square"/>
        <line x1="12" y1="3" x2="12" y2="32" stroke="#000000" stroke-width="3" stroke-linecap="square"/>
      </svg>`;
  } else {
    var color = ITEM_COLORS[kategori] || '#2563eb';
    return L.divIcon({
      className: '',
      html: `<div style="display:flex;flex-direction:column;align-items:center;">
              <div class="gis-marker" style="width:30px;height:30px;background:${color};color:#fff;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;">
                <i class="bx bx-map-pin"></i>
              </div>
              ${nameHtml}
            </div>`,
      iconSize: [80, desc ? 56 : 48], iconAnchor: [40, 15], popupAnchor: [0, -18]
    });
  }

  return L.divIcon({
    className: '',
    html: `<div style="display:flex;flex-direction:column;align-items:center;cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:center;filter:drop-shadow(0px 2px 4px rgba(0,0,0,0.35));">
              ${svgContent}
            </div>
            ${nameHtml}
          </div>`,
    iconSize: [80, desc ? 60 : 50], iconAnchor: [40, anchorY], popupAnchor: [0, -anchorY]
  });
}

function renderItem(n) {
  if (!n.latitude && !n.lat) return;
  var lat = parseFloat(n.latitude||n.lat), lng = parseFloat(n.longitude||n.lng);
  if (!lat||!lng) return;
  var desc = n.deskripsi || n.catatan || '';
  var catName = (n.kategori || 'item').replace('_', ' ').toUpperCase();
  
  var m = L.marker([lat,lng], {
    icon: makeItemIcon(n.kategori, n.status, n.nama || 'Item', desc),
    draggable: true
  });
  
  var extraInfo = `
    <div class="p-row"><span class="lbl"><i class="bx bx-purchase-tag"></i> Kategori</span><span class="val">${catName}</span></div>
    <div class="p-row"><span class="lbl"><i class="bx bx-map-pin"></i> Koordinat</span><span class="val mono">${lat.toFixed(6)}, ${lng.toFixed(6)}</span></div>
  `;
  
  m.bindPopup(buildNodePopup({
    id: n.id,
    nama: n.nama || 'Item',
    deskripsi: n.deskripsi || '',
    lat: lat,
    lng: lng,
    status: n.status || 'online',
    type: 'item'
  }, catName, extraInfo));
  
  m.on('dragend', e => savePos('item', n.id, e.target.getLatLng()));
  m.on('click', (e) => handleMarkerClick('item', n.id, m.getLatLng(), () => hiSidebar('item', n.id), e));
  L_ITEM.addLayer(m);
  markerReg['item_'+n.id] = m;
}

// ──────────────── POPUP BUILDERS ─────────────────────────────────────
function rxColorClass(rx) {
  if (!rx) return '';
  rx = parseFloat(rx);
  if (rx >= -24) return 'ok';
  if (rx >= -27) return 'wa';
  return 'ba';
}

function buildOntPopup(n, rxAwal, rxNow, rxClass) {
  var isOnline = n.status === 'online';
  var statusLbl = isOnline ? 'ONLINE' : 'OFFLINE';
  var statusCls = isOnline ? '' : 'offline';
  var uptime = n.last_inform_at ? offDuration(n.last_inform_at, !isOnline) : '—';

  var displayRxNow = isOnline ? rxNow : 'Putus';
  var displayRxClass = isOnline ? rxClass : 'r-kritis';
  var displayClients = isOnline ? '—' : '0 (Offline)';

  return `<div>
    <div class="p-hdr">
      <h6>${String(n.kode||'')} ${String(n.nama||'Pelanggan')}</h6>
      <span class="p-badge ${statusCls}">${statusLbl}</span>
      <button class="p-close" onclick="this.closest('.leaflet-popup').style.display='none'"><i class="bx bx-x"></i></button>
    </div>
    <div class="p-body">
      <div class="p-row">
        <span class="lbl"><i class="bx bx-chip"></i> ${String(n.ip_address||'—')}</span>
        <button class="ping-btn" onclick="quickPing('${n.ip_address||''}', '${n.id}')"><i class="bx bx-pulse"></i> Ping</button>
      </div>
      <div class="p-row"><span class="lbl"><i class="bx bx-broadcast"></i> ACS Status</span>
        <span class="val ${isOnline?'on':'off'}" id="acs-up-${n.id}">${isOnline ? 'Aktif · Up: '+uptime : 'Offline · Mati: '+uptime}</span>
      </div>
      <div class="p-divider"></div>
      <div class="p-redaman-row">
        <div class="p-redaman-box">
          <div class="r-lbl">Redaman Awal</div>
          <div class="r-val ${rxColorClass(n.onu_rx_baseline || -19.5)}" id="rx-base-${n.id}">${rxAwal}</div>
        </div>
        <div class="p-redaman-box">
          <div class="r-lbl">Redaman Skrg</div>
          <div class="r-val ${displayRxClass}" id="rx-now-${n.id}">${displayRxNow}</div>
        </div>
      </div>
      <div class="p-wifi-row"><span class="lbl"><i class="bx bx-wifi"></i> SSID</span><span class="val" id="wifi-ssid-${n.id}">${isOnline ? 'Memuat...' : '—'}</span></div>
      <div class="p-wifi-row"><span class="lbl"><i class="bx bx-key"></i> Password</span><span class="val pass" id="wifi-pass-${n.id}">••••••••</span></div>
      <div class="p-wifi-row"><span class="lbl"><i class="bx bx-devices"></i> Clients</span><span class="val" id="wifi-cli-${n.id}">${displayClients}</span></div>
      <div class="p-wifi-actions">
        <button class="p-wa chg" onclick="openChangeWifi(${n.id},'${String(n.nama||'')}')"><i class="bx bx-wifi"></i> Ganti WiFi</button>
        <button class="p-wa rbt" onclick="rebootOnt(${n.id},'${String(n.nama||'')}')"><i class="bx bx-power-off"></i> Reboot</button>
      </div>
      <div class="p-traffic">
        <div class="t-hdr">
          <span><i class="bx bx-line-chart"></i> Live Traffic</span>
          <span id="tr-val-${n.id}" style="color:${isOnline?'var(--muted)':'var(--red)'};">${isOnline?'TX: — RX: —':'TX: 0.0 B · RX: 0.0 B (BERHENTI)'}</span>
        </div>
        <div class="chart-container">
          <canvas id="chart-${n.id}"></canvas>
        </div>
      </div>
      <div class="p-divider"></div>
      <div class="p-actions">
        <button class="p-act" onclick="copyCoords(${n.lat},${n.lng})"><i class="bx bx-copy"></i> Salin</button>
        <button class="p-act" onclick="openGoogleMaps(${n.lat},${n.lng})"><i class="bx bx-map"></i> Maps</button>
        <button class="p-act" onclick="sendWa('${String(n.no_wa||'')}','${String(n.nama||'')}')"><i class="bx bxl-whatsapp"></i> WA</button>
        <button class="p-act edit" onclick="openEditNode('ont',${n.id})"><i class="bx bx-edit"></i> Edit / Beri Label</button>
        <button class="p-act dup" onclick="duplikatPelanggan(${n.id},'${String(n.nama||'')}')"><i class="bx bx-copy-alt"></i> DUPLIKAT</button>
      </div>
    </div>
  </div>`;
}

function buildNodePopup(n, title, extra) {
  var color = colorOf(n.status);
  return `<div>
    <div class="p-hdr">
      <h6>${title}: ${String(n.nama||'')}</h6>
      <span class="p-badge" style="background:${color}18;color:${color};">${(n.status||'online').toUpperCase()}</span>
      <button class="p-close" onclick="this.closest('.leaflet-popup').style.display='none'"><i class="bx bx-x"></i></button>
    </div>
    <div class="p-body">
      ${extra}
      ${n.deskripsi || n.catatan || n.lokasi || n.alamat ? `<div class="p-row"><span class="lbl"><i class="bx bx-purchase-tag"></i> Keterangan</span><span class="val" style="color:var(--acc);font-style:italic;">${n.deskripsi || n.catatan || n.lokasi || n.alamat}</span></div>` : ''}
      <div class="p-divider"></div>
      <div class="p-actions">
        <button class="p-act" onclick="copyCoords(${n.lat},${n.lng})"><i class="bx bx-copy"></i> Salin</button>
        <button class="p-act edit" onclick="openEditNode('${n.type}',${n.id})"><i class="bx bx-edit"></i> Edit / Beri Label</button>
        <button class="p-act" onclick="if(confirm('Hapus?'))deleteNode('${n.type}',${n.id})"><i class="bx bx-trash"></i> Hapus</button>
      </div>
    </div>
  </div>`;
}

function buildKabelPopup(k) {
  return `<div>
    <div class="p-hdr">
      <h6><i class="bx bx-git-commit"></i> ${String(k.label||'Kabel')}</h6>
      <span class="p-badge" style="background:${k.color||'#d97706'}18;color:${k.color||'#d97706'};">${(k.status||'online').toUpperCase()}</span>
      <button class="p-close" onclick="this.closest('.leaflet-popup').style.display='none'"><i class="bx bx-x"></i></button>
    </div>
    <div class="p-body">
      <div class="p-row"><span class="lbl">Tipe</span><span class="val">${String(k.tipe_label||k.tipe||'—')}</span></div>
      <div class="p-row"><span class="lbl">Monitoring</span><span class="val">${k.monitoring_type==='realtime'?'Realtime':'Manual'}</span></div>
      <div class="p-row"><span class="lbl">Core</span><span class="val">${k.jumlah_core||'—'}</span></div>
      <div class="p-row"><span class="lbl">Redaman</span><span class="val">${k.redaman_db ? k.redaman_db+' dB' : '—'}</span></div>
      ${k.catatan ? `<div class="p-row"><span class="lbl"><i class="bx bx-purchase-tag"></i> Keterangan</span><span class="val" style="color:var(--acc);font-style:italic;">${k.catatan}</span></div>` : ''}
      ${k.titik_putus_meter ? `<div class="p-row"><span class="lbl">Titik Putus</span><span class="val" style="color:var(--red);">±${k.titik_putus_meter}m</span></div>` : ''}
      <div class="p-divider"></div>
      <div class="p-actions">
        <button class="p-act" onclick="autoRouteExistingKabel(${k.id})"><i class="bx bx-git-repo-forked"></i> Auto Rute Jalan</button>
        <button class="p-act" onclick="openAutoTiangForKabel(${k.id})"><i class="bx bx-map-pin"></i> Auto Tiang</button>
        <button class="p-act edit" onclick="openEditKabel(${k.id})"><i class="bx bx-edit"></i> Edit</button>
        <button class="p-act" onclick="if(confirm('Hapus kabel?'))deleteKabel(${k.id})"><i class="bx bx-trash"></i> Hapus</button>
      </div>
    </div>
  </div>`;
}

// ──────────────── GENIEACS APP MANAGEMENT ──────────────────────────────
var currentAcsSerial = '48575443A3F1A89D';
var acsDevicesList = [];
var acsChartsObj = {};
var acsAutoRefreshTimer = null;

function openAcsConfigModal() {
  openModal('m-acs-config');
  testAcsServerConnection();
  switchAcsTab('ovw', document.getElementById('acs-tab-ovw'));

  if (acsAutoRefreshTimer) clearInterval(acsAutoRefreshTimer);
  acsAutoRefreshTimer = setInterval(() => {
    var modal = document.getElementById('m-acs-config');
    if (modal && (modal.style.display === 'flex' || modal.style.display === 'block')) {
      var activeTab = document.querySelector('#m-acs-config .tp.active');
      if (activeTab) {
        var id = activeTab.id.replace('acs-tab-', '');
        if (id === 'ovw') loadAcsOverviewCharts();
        else if (id === 'dev') loadAcsDevices();
        else if (id === 'det' && currentAcsSerial) loadAcsDeviceDetail(currentAcsSerial);
      }
    } else {
      clearInterval(acsAutoRefreshTimer);
    }
  }, 15000);
}

function switchAcsTab(tabName, btn) {
  document.querySelectorAll('#m-acs-config .tp').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  document.querySelectorAll('#m-acs-config .acs-sec').forEach(s => s.style.display = 'none');
  var sec = document.getElementById('acs-sec-' + tabName);
  if (sec) sec.style.display = 'block';

  if (tabName === 'ovw') loadAcsOverviewCharts();
  else if (tabName === 'dev' && (!acsDevicesList || !acsDevicesList.length)) loadAcsDevices();
  else if (tabName === 'pst') loadAcsPresets();
  else if (tabName === 'prv') loadAcsProvisions();
  else if (tabName === 'flt') loadAcsFaults();
  else if (tabName === 'fls') loadAcsFiles();
}

async function loadAcsOverviewCharts() {
  try {
    var res = await fetch(`${BASE}/ftth/api/acs/devices`, {headers:{Accept:'application/json'}});
    var json = await res.json();
    var list = json.devices || [];

    var statusCount = { on: 0, off: 0 };
    var accessCount = { GPON: 0, EPON: 0, Ethernet: 0 };
    var rxCount     = { Bagus: 0, Sedang: 0, Warning: 0, Strong: 0 };
    var vendorCount = {};
    var tempCount   = { cold: 0, warm: 0, hot: 0 };
    var wifiCount   = { empty: 0, normal: 0, medium: 0, over: 0 };

    if (!list.length) {
      createAcsPieChart('chart-acs-status', ['Belum Ada Device'], [1], ['#cbd5e1']);
      createAcsPieChart('chart-acs-access', ['Belum Ada Device'], [1], ['#cbd5e1']);
      createAcsPieChart('chart-acs-rx', ['Belum Ada Device'], [1], ['#cbd5e1']);
      createAcsPieChart('chart-acs-vendor', ['Belum Ada Device'], [1], ['#cbd5e1']);
      createAcsPieChart('chart-acs-temp', ['Belum Ada Device'], [1], ['#cbd5e1']);
      createAcsPieChart('chart-acs-wifi', ['Belum Ada Device'], [1], ['#cbd5e1']);
      return;
    }

    list.forEach(d => {
      if (d.is_online) statusCount.on++; else statusCount.off++;

      var model = (d.model || '').toUpperCase();
      if (model.includes('EPON')) accessCount.EPON++;
      else if (model.includes('GPON')) accessCount.GPON++;
      else accessCount.EPON++;

      var rx = parseFloat(d.rx_power);
      if (!isNaN(rx)) {
        if (rx >= -25 && rx <= -10) rxCount.Bagus++;
        else if (rx < -25 && rx >= -28) rxCount.Sedang++;
        else if (rx > -10) rxCount.Strong++;
        else rxCount.Warning++;
      } else {
        rxCount.Bagus++;
      }

      var v = d.manufacturer || 'Other';
      vendorCount[v] = (vendorCount[v] || 0) + 1;

      tempCount.warm++;
      wifiCount.normal++;
    });

    createAcsPieChart('chart-acs-status', ['Online', 'Offline'], [statusCount.on, statusCount.off], ['#16a34a', '#dc2626']);
    createAcsPieChart('chart-acs-access', ['EPON', 'GPON', 'Ethernet'], [accessCount.EPON, accessCount.GPON, accessCount.Ethernet], ['#84cc16', '#2563eb', '#a855f7']);
    createAcsPieChart('chart-acs-rx', ['Bagus (-10 s/d -25)', 'Sedang', 'Warning (< -28)', 'Kuat'], [rxCount.Bagus, rxCount.Sedang, rxCount.Warning, rxCount.Strong], ['#06b6d4', '#f59e0b', '#dc2626', '#3b82f6']);
    createAcsPieChart('chart-acs-vendor', Object.keys(vendorCount), Object.values(vendorCount), ['#0284c7', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899']);
    createAcsPieChart('chart-acs-temp', ['Warm (45-60°C)', 'Cold (<45°C)', 'Hot (>60°C)'], [tempCount.warm, tempCount.cold, tempCount.hot], ['#eab308', '#3b82f6', '#dc2626']);
    createAcsPieChart('chart-acs-wifi', ['Normal (0-5)', 'Medium (6-10)', 'Over (>10)', 'Empty'], [wifiCount.normal, wifiCount.medium, wifiCount.over, wifiCount.empty], ['#22c55e', '#f59e0b', '#dc2626', '#94a3b8']);

  } catch(e) { console.error('Overview Charts Error:', e); }
}

function createAcsPieChart(canvasId, labels, data, colors) {
  var ctx = document.getElementById(canvasId);
  if (!ctx) return;

  if (acsChartsObj[canvasId]) {
    acsChartsObj[canvasId].destroy();
  }

  acsChartsObj[canvasId] = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 6, font: { size: 8 } } }
      }
    }
  });
}

async function loadAcsPresets() {
  var tbody = document.getElementById('acs-pst-tbody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;"><i class="bx bx-loader-alt bx-spin"></i> Memuat Presets...</td></tr>';
  try {
    var res = await fetch(`${BASE}/ftth/api/acs/presets`, {headers:{Accept:'application/json'}});
    var json = await res.json();
    var list = json.presets || [];
    if (!list.length) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;color:var(--muted);">Belum ada Preset terdaftar.</td></tr>';
      return;
    }
    if (tbody) {
      tbody.innerHTML = list.map(p => `
        <tr>
          <td><b>${p._id || '-'}</b></td>
          <td><code>${p.weight || 0}</code></td>
          <td><small>${p.precondition || '-'}</small></td>
          <td><span class="ni-badge s-on">${p.events ? Object.keys(p.events).join(', ') : '-'}</span></td>
        </tr>`).join('');
    }
  } catch(e) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:10px;color:var(--red);">Error: ${e.message}</td></tr>`;
  }
}

async function loadAcsProvisions() {
  var tbody = document.getElementById('acs-prv-tbody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;padding:10px;"><i class="bx bx-loader-alt bx-spin"></i> Memuat Provisions...</td></tr>';
  try {
    var res = await fetch(`${BASE}/ftth/api/acs/provisions`, {headers:{Accept:'application/json'}});
    var json = await res.json();
    var list = json.provisions || [];
    if (!list.length) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;padding:10px;color:var(--muted);">Belum ada Provision Script terdaftar.</td></tr>';
      return;
    }
    if (tbody) {
      tbody.innerHTML = list.map(p => `
        <tr>
          <td><b>${p._id || '-'}</b></td>
          <td><code>${p._id || '-'}</code></td>
        </tr>`).join('');
    }
  } catch(e) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="2" style="text-align:center;padding:10px;color:var(--red);">Error: ${e.message}</td></tr>`;
  }
}

async function loadAcsFaults() {
  var tbody = document.getElementById('acs-flt-tbody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;"><i class="bx bx-loader-alt bx-spin"></i> Memuat Faults Log...</td></tr>';
  try {
    var res = await fetch(`${BASE}/ftth/api/acs/faults`, {headers:{Accept:'application/json'}});
    var json = await res.json();
    var list = json.faults || [];
    if (!list.length) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;color:var(--muted);">Tidak ada Faults Log terdeteksi.</td></tr>';
      return;
    }
    if (tbody) {
      tbody.innerHTML = list.map(f => `
        <tr>
          <td><code>${f.device || f._id || '-'}</code></td>
          <td><span class="r-val rx-cr">${f.code || '-'}</span></td>
          <td><small>${f.message || '-'}</small></td>
          <td><small style="color:var(--muted);">${f.timestamp || '-'}</small></td>
        </tr>`).join('');
    }
  } catch(e) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:10px;color:var(--red);">Error: ${e.message}</td></tr>`;
  }
}

async function loadAcsFiles() {
  var tbody = document.getElementById('acs-fls-tbody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;"><i class="bx bx-loader-alt bx-spin"></i> Memuat Files FS...</td></tr>';
  try {
    var res = await fetch(`${BASE}/ftth/api/acs/files`, {headers:{Accept:'application/json'}});
    var json = await res.json();
    var list = json.files || [];
    if (!list.length) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">Belum ada File Firmware/Config terdaftar di GenieACS FS.</td></tr>';
      return;
    }
    if (tbody) {
      tbody.innerHTML = list.map(f => {
        var fname = f._id || f.filename || '-';
        return `
        <tr>
          <td><b>${fname}</b></td>
          <td><code>${f.metadata?.fileType || f.metadata?.type || '-'}</code></td>
          <td><small>${f.metadata?.version || '-'}</small></td>
          <td style="text-align:center;">
            <button class="p-act" style="padding:2px 6px;font-size:9px;color:var(--red);margin:0 auto;" onclick="deleteAcsFileDirect('${fname}')" title="Hapus File"><i class="bx bx-trash"></i></button>
          </td>
        </tr>`;
      }).join('');
    }
  } catch(e) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:10px;color:var(--red);">Error: ${e.message}</td></tr>`;
  }
}

async function submitAcsFileUpload(e) {
  e.preventDefault();
  var fileInput = document.getElementById('acs-file-input');
  var typeInput = document.getElementById('acs-file-type');
  var verInput  = document.getElementById('acs-file-ver');

  if (!fileInput || !fileInput.files.length) {
    showToast('Pilih file firmware / konfigurasi terlebih dahulu', 'er');
    return;
  }

  var file = fileInput.files[0];
  var formData = new FormData();
  formData.append('file', file);
  formData.append('file_type', typeInput?.value || '1 Firmware Upgrade Image');
  formData.append('version', verInput?.value || '1.0.0');

  showToast('Mengunggah ' + file.name + ' ke GenieACS FS...', 'ok');

  try {
    var res = await fetch(`${BASE}/ftth/api/acs/files`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
      },
      body: formData,
    });
    var json = await res.json();
    if (!res.ok || !json.success) throw new Error(json.message || 'Gagal upload file');

    showToast(json.message || 'File berhasil diunggah ke GenieACS FS!', 'ok');
    fileInput.value = '';
    loadAcsFiles();
  } catch (err) {
    showToast('Upload Error: ' + err.message, 'er');
  }
}

async function deleteAcsFileDirect(filename) {
  if (!confirm(`Hapus file "${filename}" dari GenieACS FS?`)) return;
  showToast('Menghapus ' + filename + '...', 'ok');
  try {
    var res = await fetch(`${BASE}/ftth/api/acs/files/${encodeURIComponent(filename)}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
      }
    });
    var json = await res.json();
    if (!res.ok || !json.success) throw new Error(json.message || 'Gagal hapus file');
    showToast(json.message || 'File berhasil dihapus!', 'ok');
    loadAcsFiles();
  } catch (err) {
    showToast('Gagal hapus: ' + err.message, 'er');
  }
}

async function testAcsServerConnection() {
  var st = document.getElementById('acs-conn-status');
  if (st) st.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Checking NBI...';

  var url = document.getElementById('acs-nbi-url')?.value?.trim();
  var user = document.getElementById('acs-nbi-user')?.value?.trim();
  var pass = document.getElementById('acs-nbi-pass')?.value?.trim();

  try {
    var query = url ? `?url=${encodeURIComponent(url)}&user=${encodeURIComponent(user||'')}&pass=${encodeURIComponent(pass||'')}` : '';
    var res = await fetch(`${BASE}/ftth/api/acs/test${query}`, {headers:{Accept:'application/json'}});
    var json = await res.json();

    if (json.online) {
      if (st) st.innerHTML = `<span style="color:var(--green);"><i class="bx bx-check-circle"></i> Connected (${json.url})</span>`;
    } else {
      if (st) st.innerHTML = `<span style="color:var(--red);"><i class="bx bx-x-circle"></i> NBI Offline</span>`;
    }
  } catch(e) {
    if (st) st.innerHTML = `<span style="color:var(--red);"><i class="bx bx-x-circle"></i> Connection Error</span>`;
  }
}

async function saveAcsUrl() {
  var url = document.getElementById('acs-nbi-url')?.value?.trim();
  var user = document.getElementById('acs-nbi-user')?.value?.trim();
  var pass = document.getElementById('acs-nbi-pass')?.value?.trim();

  if (!url) { showToast('NBI Server URL harus diisi', 'er'); return; }

  showToast('Menyimpan pengaturan GenieACS NBI secara dinamis...', 'ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/acs/config`, { url: url, user: user, pass: pass });
    showToast(res.message || 'Pengaturan GenieACS NBI tersimpan!', 'ok');
    testAcsServerConnection();
    loadAcsDevices();
  } catch(e) {
    showToast('Gagal simpan config: ' + e.message, 'er');
  }
}

async function loadAcsDevices() {
  var tbody = document.getElementById('acs-dev-tbody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--muted);"><i class="bx bx-loader-alt bx-spin"></i> Memuat perangkat dari GenieACS...</td></tr>';

  try {
    var res = await fetch(`${BASE}/ftth/api/acs/devices`, {headers:{Accept:'application/json'}});
    var json = await res.json();

    if (!json.success) {
      if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--red);"><i class="bx bx-error-circle"></i> ${json.message || 'Gagal memuat perangkat dari GenieACS'}</td></tr>`;
      return;
    }

    acsDevicesList = json.devices || [];
    var cnt = document.getElementById('acs-dev-cnt');
    if (cnt) cnt.textContent = acsDevicesList.length;

    renderAcsTable(acsDevicesList);
  } catch(e) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--red);"><i class="bx bx-error-circle"></i> Error: ${e.message}</td></tr>`;
  }
}

function renderAcsTable(list) {
  var tbody = document.getElementById('acs-dev-tbody');
  if (!tbody) return;

  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--muted);">Tidak ada perangkat TR-069 ditemukan.</td></tr>';
    return;
  }

  tbody.innerHTML = list.map(d => {
    var stCls = d.is_online ? 's-on' : 's-of';
    var stTxt = d.is_online ? 'ONLINE' : 'OFFLINE';
    var rxCls = rxColorClass(d.rx_power);
    var rxTxt = d.rx_power ? `${d.rx_power} dBm` : '—';
    var informTxt = d.last_inform ? offDuration(d.last_inform, !d.is_online) : '—';

    return `<tr>
      <td><code style="font-weight:700;color:var(--acc);">${d.serial_id}</code></td>
      <td><b>${d.manufacturer}</b> <small style="color:var(--muted);">${d.model}</small></td>
      <td><code>${d.ip_address}</code><br><small style="color:var(--muted);">${d.pppoe_user}</small></td>
      <td><span class="r-val ${rxCls}">${rxTxt}</span></td>
      <td><b>${d.ssid}</b></td>
      <td><span class="ni-badge ${stCls}">${stTxt}</span> <small style="font-size:9px;color:var(--muted);">${informTxt}</small></td>
      <td>
        <div style="display:flex;gap:3px;">
          <button class="ping-btn" onclick="viewAcsDeviceDetail('${d.serial_id}')"><i class="bx bx-chip"></i> Detail</button>
          <button class="p-wa rbt" style="padding:2px 6px;font-size:9px;" onclick="rebootAcsDeviceDirect('${d.serial_id}')"><i class="bx bx-power-off"></i></button>
          <button class="p-act" style="padding:2px 6px;font-size:9px;color:var(--red);" onclick="deleteAcsDeviceDirect('${d.serial_id}')"><i class="bx bx-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function filterAcsTable(q) {
  if (!q) { renderAcsTable(acsDevicesList); return; }
  q = q.toLowerCase();
  var filtered = acsDevicesList.filter(d => {
    return (d.serial_id || '').toLowerCase().includes(q) ||
           (d.ip_address || '').toLowerCase().includes(q) ||
           (d.pppoe_user || '').toLowerCase().includes(q) ||
           (d.model || '').toLowerCase().includes(q) ||
           (d.ssid || '').toLowerCase().includes(q);
  });
  renderAcsTable(filtered);
}

async function viewAcsDeviceDetail(serialId) {
  currentAcsSerial = serialId;
  switchAcsTab('det', document.getElementById('acs-tab-det'));

  var emptyDiv = document.getElementById('acs-det-empty');
  var contentDiv = document.getElementById('acs-det-content');

  if (emptyDiv) emptyDiv.innerHTML = '<div style="padding:30px;text-align:center;"><i class="bx bx-loader-alt bx-spin" style="font-size:24px;color:var(--acc);"></i><br>Memuat telemetri lengkap ' + serialId + '...</div>';
  if (contentDiv) contentDiv.style.display = 'none';

  try {
    var res = await fetch(`${BASE}/ftth/api/acs/devices/${encodeURIComponent(serialId)}`, {headers:{Accept:'application/json'}});
    var json = await res.json();

    if (!json.success || !json.device) {
      if (emptyDiv) emptyDiv.innerHTML = '<div style="color:var(--red);padding:20px;text-align:center;">Gagal memuat detail device dari GenieACS.</div>';
      return;
    }

    var dev = json.device;
    if (emptyDiv) emptyDiv.style.display = 'none';
    if (contentDiv) contentDiv.style.display = 'block';

    document.getElementById('det-serial-title').textContent = `Serial: ${dev.serial_id}`;
    document.getElementById('det-vendor-sub').textContent = `Vendor: ${dev.manufacturer} | Model: ${dev.model} | FW: ${dev.software_ver}`;

    document.getElementById('det-wifi-ssid').value = dev.wifi_24_ssid || '';
    document.getElementById('det-wifi-pass').value = dev.wifi_24_pass || '';

    var rxEl = document.getElementById('det-rx-val');
    if (rxEl) {
      rxEl.textContent = dev.rx_power ? `${dev.rx_power} dBm` : '—';
      rxEl.className = `r-val ${rxColorClass(dev.rx_power)}`;
    }

    document.getElementById('det-ip-val').textContent = dev.ip_address || '—';
    document.getElementById('det-uptime-val').textContent = dev.uptime_formatted || '—';
    document.getElementById('det-inform-val').textContent = dev.last_inform ? offDuration(dev.last_inform, !dev.is_online) : '—';

    document.getElementById('det-pppoe-user').value = dev.pppoe_user || '';
    document.getElementById('det-pppoe-pass').value = '';

    // Render connected clients
    var cliCnt = document.getElementById('det-cli-cnt');
    if (cliCnt) cliCnt.textContent = dev.clients_count || 0;

    var cliTbody = document.getElementById('det-cli-tbody');
    if (cliTbody) {
      if (!dev.clients || !dev.clients.length) {
        cliTbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;color:var(--muted);">Tidak ada client terdeteksi.</td></tr>';
      } else {
        cliTbody.innerHTML = dev.clients.map(c => `
          <tr>
            <td><b>${c.hostname}</b></td>
            <td><code>${c.ip}</code></td>
            <td><code style="color:var(--muted);">${c.mac}</code></td>
            <td><span class="ni-badge s-on">${c.type}</span></td>
          </tr>`).join('');
      }
    }
  } catch(e) {
    if (emptyDiv) emptyDiv.innerHTML = `<div style="color:var(--red);padding:20px;text-align:center;">Error: ${e.message}</div>`;
  }
}

async function saveCurrentAcsWifi() {
  var serial = currentAcsSerial || '48575443A3F1A89D';
  var ssidInput = document.getElementById('det-wifi-ssid');
  var passInput = document.getElementById('det-wifi-pass');
  var ssid = ssidInput?.value?.trim();
  var pass = passInput?.value?.trim();

  if (!ssid && !pass) { showToast('Isi SSID atau password WiFi baru', 'er'); return; }

  showToast('Mengirim perintah ganti WiFi...', 'ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/acs/devices/${encodeURIComponent(serial)}/wifi`, {
      ssid: ssid, password: pass
    });

    if (ssid && ssidInput) ssidInput.value = ssid;
    if (pass && passInput) passInput.value = pass;

    showToast(res.message || `✓ Wi-Fi berhasil diubah menjadi "${ssid}"!`, 'ok');

    // Update in local devices list and re-render Devices table
    var dObj = acsDevicesList.find(x => x.serial_id === serial || (x.serial_id && x.serial_id.includes(serial)));
    if (dObj) {
      if (ssid) { dObj.ssid = ssid; dObj.wifi_24_ssid = ssid; }
      if (pass) { dObj.wifi_pass = pass; dObj.wifi_24_pass = pass; }
    }
    if (typeof renderAcsTable === 'function') renderAcsTable(acsDevicesList);

    // Refresh devices table in background
    setTimeout(() => { if (typeof loadAcsDevices === 'function') loadAcsDevices(); }, 500);
  } catch(e) { showToast('Gagal ganti WiFi: ' + e.message, 'er'); }
}

async function saveCurrentAcsPppoe() {
  if (!currentAcsSerial) return;
  var user = document.getElementById('det-pppoe-user')?.value?.trim();
  var pass = document.getElementById('det-pppoe-pass')?.value?.trim();

  if (!user || !pass) { showToast('Isi PPPoE username dan password', 'er'); return; }

  showToast('Mengirim perintah update PPPoE WAN...', 'ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/acs/devices/${encodeURIComponent(currentAcsSerial)}/pppoe`, {
      username: user, password: pass
    });
    showToast(res.message || 'PPPoE berhasil diperbarui', 'ok');
  } catch(e) { showToast('Gagal update PPPoE: ' + e.message, 'er'); }
}

async function rebootCurrentAcsDevice() {
  if (!currentAcsSerial) return;
  if (!confirm(`Reboot device ${currentAcsSerial}?`)) return;
  rebootAcsDeviceDirect(currentAcsSerial);
}

async function rebootAcsDeviceDirect(serialId) {
  showToast(`Mengirim perintah reboot ke ${serialId}...`, 'ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/acs/devices/${encodeURIComponent(serialId)}/reboot`, {});
    showToast(res.message || 'Perintah reboot terkirim', 'ok');
  } catch(e) { showToast('Gagal reboot: ' + e.message, 'er'); }
}

async function factoryResetCurrentAcsDevice() {
  if (!currentAcsSerial) return;
  if (!confirm(`RESET FACTORY device ${currentAcsSerial}?\nSeluruh pengaturan WAN & WiFi di ONT akan terhapus!`)) return;

  showToast(`Mengirim perintah Factory Reset ke ${currentAcsSerial}...`, 'ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/acs/devices/${encodeURIComponent(currentAcsSerial)}/factory-reset`, {});
    showToast(res.message || 'Perintah Reset terkirim', 'ok');
  } catch(e) { showToast('Gagal Factory Reset: ' + e.message, 'er'); }
}

async function refreshCurrentAcsDevice() {
  if (!currentAcsSerial) return;
  showToast(`Mengirim perintah Refresh TR-069 ke ${currentAcsSerial}...`, 'ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/acs/devices/${encodeURIComponent(currentAcsSerial)}/refresh`, {});
    showToast(res.message || 'Perintah Refresh terkirim', 'ok');
    setTimeout(() => viewAcsDeviceDetail(currentAcsSerial), 2000);
  } catch(e) { showToast('Gagal Refresh: ' + e.message, 'er'); }
}

async function deleteAcsDeviceDirect(serialId) {
  if (!confirm(`Hapus device ${serialId} dari GenieACS NBI?`)) return;
  showToast(`Menghapus ${serialId} dari GenieACS...`, 'ok');
  try {
    var res = await api('DELETE', `${BASE}/ftth/api/acs/devices/${encodeURIComponent(serialId)}`);
    showToast(res.message || 'Device terhapus', 'ok');
    loadAcsDevices();
  } catch(e) { showToast('Gagal hapus: ' + e.message, 'er'); }
}

// ──────────────── AUTO GENERATE TIANG ─────────────────────────────────
function openAutoTiangModal(selectedKabelId) {
  var select = document.getElementById('at-kabel-select');
  if (!select) return;
  if (!DATA.kabels || !DATA.kabels.length) {
    showToast('Belum ada data kabel. Buat kabel terlebih dahulu.','er');
    return;
  }
  select.innerHTML = DATA.kabels.map(k => `
    <option value="${k.id}" ${selectedKabelId == k.id ? 'selected' : ''}>
      ${k.label || 'Kabel #'+k.id} (${k.tipe || 'distribusi'}, ${k.jumlah_core||0} core)
    </option>`).join('');
  openModal('m-auto-tiang');
}

function openAutoTiangForKabel(kabelId) {
  openAutoTiangModal(kabelId);
}

async function submitAutoGenerateTiang() {
  var kabelId = document.getElementById('at-kabel-select')?.value;
  var jarak = parseInt(document.getElementById('at-jarak')?.value || 50);
  if (!kabelId) { showToast('Pilih kabel terlebih dahulu','er'); return; }
  showToast('Mengenerate tiang tumpu...','ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/items/auto-generate-tiang`, {
      kabel_id: parseInt(kabelId),
      jarak_meter: jarak
    });
    showToast(`Berhasil! ${res.created} tiang tumpu telah dibuat.`,'ok');
    closeModal('m-auto-tiang');
    loadAll(true);
  } catch(e) {
    showToast('Auto Generate Error: ' + e.message, 'er');
  }
}

// ──────────────── DRAW CABLE (WITH OSRM ROAD ROUTING & DIRECT MODAL) ───
function startDrawCable() {
  drawActive = true; drawPts = [];
  document.getElementById('draw-banner').classList.add('show');
  MAP.getContainer().style.cursor = 'crosshair';
  showToast('Modus gambar kabel aktif! Klik lokasi-lokasi di peta.','ok');
}

async function addDrawPt(latlng) {
  var newPt = [latlng.lat, latlng.lng];
  var useRoute = document.getElementById('cb-auto-route')?.checked;

  if (drawPts.length > 0 && useRoute) {
    var lastPt = drawPts[drawPts.length - 1];
    showToast('Menghitung rute jalan (OSRM)...','ok');
    try {
      var controller = new AbortController();
      var timeoutId = setTimeout(() => controller.abort(), 2000);

      var url1 = `https://routing.openstreetmap.de/routed-car/route/v1/driving/${lastPt[1]},${lastPt[0]};${newPt[1]},${newPt[0]}?overview=full&geometries=geojson`;
      var res = await fetch(url1, { signal: controller.signal });
      clearTimeout(timeoutId);

      var json = await res.json();
      if (json.routes && json.routes[0] && json.routes[0].geometry && json.routes[0].geometry.coordinates.length) {
        var coords = json.routes[0].geometry.coordinates; // [[lng, lat], ...]
        coords.forEach(c => drawPts.push([c[1], c[0]]));
        drawPts.push(newPt); // Always append target point so it connects to the device icon!
      } else {
        drawPts.push(newPt);
      }
    } catch(e) {
      try {
        var url2 = `https://router.project-osrm.org/route/v1/driving/${lastPt[1]},${lastPt[0]};${newPt[1]},${newPt[0]}?overview=full&geometries=geojson`;
        var res2 = await fetch(url2);
        var json2 = await res2.json();
        if (json2.routes && json2.routes[0] && json2.routes[0].geometry && json2.routes[0].geometry.coordinates.length) {
          json2.routes[0].geometry.coordinates.forEach(c => drawPts.push([c[1], c[0]]));
          drawPts.push(newPt);
        } else {
          drawPts.push(newPt);
        }
      } catch(err) {
        drawPts.push(newPt);
      }
    }
  } else {
    drawPts.push(newPt);
  }

  var m = L.circleMarker(latlng,{radius:6,color:'#0284c7',fillColor:'#0284c7',fillOpacity:1}).addTo(MAP);
  drawTmpMarkers.push(m);
  if (drawTmpLine) MAP.removeLayer(drawTmpLine);
  if (drawPts.length > 1) {
    drawTmpLine = L.polyline(drawPts,{color:'#0284c7',weight:4,dashArray:'6,4'}).addTo(MAP);
  }
}

async function autoRouteExistingKabel(id) {
  var k = DATA.kabels.find(x => x.id == id);
  if (!k || !k.geometry || k.geometry.length < 2) return;
  var startPt = k.geometry[0];
  var endPt = k.geometry[k.geometry.length - 1];

  showToast('Menghitung rute jalan presisi (OSRM)...', 'ok');
  try {
    var url = `https://routing.openstreetmap.de/routed-car/route/v1/driving/${startPt[1]},${startPt[0]};${endPt[1]},${endPt[0]}?overview=full&geometries=geojson`;
    var res = await fetch(url);
    var json = await res.json();

    if (json.routes && json.routes[0] && json.routes[0].geometry && json.routes[0].geometry.coordinates.length) {
      var roadCoords = json.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
      var newCoords = [startPt, ...roadCoords, endPt];
      await api('PUT', `${BASE}/ftth/api/kabel/${id}`, { geometry: newCoords });
      showToast(`Kabel "${k.label}" berhasil disambungkan 100% sampai ke perangkat!`, 'ok');
      loadAll(true);
    } else {
      showToast('Tidak dapat menemukan rute jalan terdekat', 'er');
    }
  } catch(e) {
    showToast('Gagal menghitung rute jalan: ' + e.message, 'er');
  }
}

function findClosestNode(lat, lng) {
  var all = [
    ...(DATA.olts||[]).map(o => ({...o, type:'olt'})),
    ...(DATA.odcOdps||[]).map(o => ({...o, type:(o.tipe||'odc').toLowerCase()})),
    ...(DATA.pelanggan||[]).map(p => ({...p, type:'pelanggan'})),
    ...(DATA.ftthItems||[]).map(i => ({...i, type:'ftth_item'}))
  ];
  var minD = Infinity, closest = null;
  var pt = L.latLng(lat, lng);
  all.forEach(n => {
    if (n.lat && n.lng) {
      var d = pt.distanceTo(L.latLng(n.lat, n.lng));
      if (d < minD) { minD = d; closest = n; }
    }
  });
  return closest;
}

function populateCableNodeSelects(startPt, endPt) {
  var fromSel = document.getElementById('nk-from');
  var toSel = document.getElementById('nk-to');
  if (!fromSel || !toSel) return;

  var all = [
    ...(DATA.olts||[]).map(o => ({...o, type:'olt', badge:'[OLT]'})),
    ...(DATA.odcOdps||[]).map(o => ({...o, type:(o.tipe||'odc').toLowerCase(), badge: '['+(o.tipe||'ODC').toUpperCase()+']'})),
    ...(DATA.pelanggan||[]).map(p => ({...p, type:'pelanggan', badge:'[ONT]', nama: p.nama || p.kode || 'Pelanggan #'+p.id})),
    ...(DATA.ftthItems||[]).map(i => ({...i, type:'ftth_item', badge:'['+(i.kategori||'ITEM').toUpperCase()+']', nama: i.nama || i.kode || 'Item #'+i.id}))
  ];

  if (!all.length) {
    fromSel.innerHTML = '<option value="olt_1" data-nama="olt hioso">[OLT] olt hioso</option>';
    toSel.innerHTML = '<option value="odc_1" data-nama="odc hioso">[ODC] odc hioso</option>';
    autoGenCableLabel();
    return;
  }

  var optionsHtml = all.map(n => `<option value="${n.type}_${n.id}" data-nama="${n.nama}">${n.badge||''} ${n.nama || 'Node #'+n.id}</option>`).join('');

  fromSel.innerHTML = optionsHtml;
  toSel.innerHTML = optionsHtml;

  var closestStart = (startPt && startPt.length >= 2) ? findClosestNode(startPt[0], startPt[1]) : null;
  var closestEnd = (endPt && endPt.length >= 2) ? findClosestNode(endPt[0], endPt[1]) : null;

  if (closestStart) {
    var valStart = `${closestStart.type}_${closestStart.id}`;
    if ([...fromSel.options].some(o => o.value === valStart)) {
      fromSel.value = valStart;
    } else {
      fromSel.selectedIndex = 0;
    }
  } else {
    fromSel.selectedIndex = 0;
  }

  if (closestEnd && closestEnd !== closestStart) {
    var valEnd = `${closestEnd.type}_${closestEnd.id}`;
    if ([...toSel.options].some(o => o.value === valEnd)) {
      toSel.value = valEnd;
    } else if (toSel.options.length > 1) {
      toSel.selectedIndex = 1;
    }
  } else if (toSel.options.length > 1) {
    toSel.selectedIndex = (fromSel.selectedIndex === 0) ? 1 : 0;
  }

  autoGenCableLabel();
}

function autoGenCableLabel() {
  var fromSel = document.getElementById('nk-from');
  var toSel = document.getElementById('nk-to');
  var lblInput = document.getElementById('nk-label');
  if (!fromSel || !toSel || !lblInput) return;

  var fromOpt = fromSel.options[fromSel.selectedIndex];
  var toOpt = toSel.options[toSel.selectedIndex];
  var fromNama = fromOpt ? fromOpt.getAttribute('data-nama') : 'odc-c320-c1.';
  var toNama = toOpt ? toOpt.getAttribute('data-nama') : 'odp-c320-c1-c1';

  lblInput.value = `${fromNama} - ${toNama}`;
}

function finishDraw() {
  if (drawPts.length < 2) { showToast('Minimal 2 titik untuk membuat kabel!','er'); return; }
  window.pendingCablePts = drawPts.slice();
  cancelDraw(true);

  var totalMeters = 0;
  for (var i = 0; i < window.pendingCablePts.length - 1; i++) {
    var p1 = L.latLng(window.pendingCablePts[i][0], window.pendingCablePts[i][1]);
    var p2 = L.latLng(window.pendingCablePts[i+1][0], window.pendingCablePts[i+1][1]);
    totalMeters += Math.round(p1.distanceTo(p2));
  }

  var startPt = window.pendingCablePts[0];
  var endPt = window.pendingCablePts[window.pendingCablePts.length - 1];
  populateCableNodeSelects(startPt, endPt);

  var infoBox = document.getElementById('nk-info-box');
  if (infoBox) {
    infoBox.innerHTML = `
      <b><i class="bx bx-ruler"></i> Total Panjang:</b> ${totalMeters} meter<br>
      <b><i class="bx bx-map-pin"></i> Jumlah Titik Rute:</b> ${window.pendingCablePts.length} koordinat`;
  }
  openModal('m-simpan-kabel');
}

function cancelDraw(clear=true) {
  drawActive = false;
  document.getElementById('draw-banner').classList.remove('show');
  MAP.getContainer().style.cursor = '';
  if (drawTmpMarkers && drawTmpMarkers.length) {
    drawTmpMarkers.forEach(m => {
      try { MAP.removeLayer(m); } catch(e){}
    });
    drawTmpMarkers = [];
  }
  if (drawTmpLine) {
    try { MAP.removeLayer(drawTmpLine); } catch(e){}
    drawTmpLine = null;
  }
  if (clear) {
    drawPts = [];
  }
}

function onCableTypeSelectChange() {
  var tipe = document.getElementById('nk-tipe')?.value || 'distribusi';
  var colorMap = {
    feeder: '#0284c7',        // Cyan
    distribusi: '#d97706',    // Kuning/Orange
    backbone: '#8b5cf6',      // Ungu
    trunk: '#ec4899',         // Magenta
    sub_distribusi: '#10b981',// Hijau Emerald
    drop: '#2563eb'           // Biru
  };
  var color = colorMap[tipe] || '#d97706';
  var textInput = document.getElementById('nk-color');
  var pickerInput = document.getElementById('nk-color-picker');
  if (textInput) textInput.value = color;
  if (pickerInput) pickerInput.value = color;
  updateCableRealtimePreview();
}

function updateCableRealtimePreview() {
  var tipe = document.getElementById('nk-tipe')?.value || 'distribusi';
  var colorInput = document.getElementById('nk-color')?.value?.trim();
  
  var colorMap = {
    feeder: '#0284c7',        // Cyan
    distribusi: '#d97706',    // Kuning/Orange
    backbone: '#8b5cf6',      // Ungu
    trunk: '#ec4899',         // Magenta
    sub_distribusi: '#10b981',// Hijau Emerald
    drop: '#2563eb'           // Biru
  };

  var selectedColor = colorInput || colorMap[tipe] || '#d97706';

  var picker = document.getElementById('nk-color-picker');
  if (picker && /^#[0-9A-F]{6}$/i.test(selectedColor)) {
    picker.value = selectedColor;
  }

  // Update real-time polyline on map immediately if drawing line exists!
  if (drawTmpLine) {
    drawTmpLine.setStyle({ color: selectedColor });
  }
}

async function submitNewKabel() {
  var geometry = window.pendingCablePts || [];
  if (!geometry || !geometry.length) { showToast('Tidak ada titik jalur kabel','er'); return; }
  var label = document.getElementById('nk-label')?.value?.trim();
  if (!label) { showToast('Label kabel wajib diisi','er'); return; }

  var fromVal = document.getElementById('nk-from')?.value || '';
  var toVal = document.getElementById('nk-to')?.value || '';
  var fromParts = fromVal.split('_');
  var toParts = toVal.split('_');

  var colorMap = { feeder: '#0284c7', distribusi: '#d97706', backbone: '#8b5cf6', trunk: '#ec4899', sub_distribusi: '#10b981', drop: '#2563eb' };
  var tipeVal = document.getElementById('nk-tipe')?.value || 'distribusi';
  var colorVal = document.getElementById('nk-color')?.value?.trim() || colorMap[tipeVal];

  var payload = {
    label,
    tipe: tipeVal,
    color: colorVal,
    monitoring_type: document.getElementById('nk-mon')?.value || 'manual',
    from_type: fromParts[0] || 'olt',
    from_id: parseInt(fromParts[1] || 1),
    to_type: toParts[0] || 'odc',
    to_id: parseInt(toParts[1] || 1),
    jumlah_core: parseInt(document.getElementById('nk-core')?.value || 12),
    catatan: document.getElementById('nk-cat')?.value || '',
    geometry,
  };

  showToast(window.editingCableId ? 'Mengupdate kabel...' : 'Menyimpan kabel ke peta...','ok');
  try {
    if (window.editingCableId) {
      await api('PUT', `${BASE}/ftth/api/kabel/${window.editingCableId}`, payload);
      showToast('Kabel "' + label + '" berhasil diupdate!','ok');
      window.editingCableId = null;
    } else {
      await api('POST', `${BASE}/ftth/api/kabel`, payload);
      showToast('Kabel "' + label + '" berhasil disimpan ke peta!','ok');
    }
    window.pendingCablePts = null;
    closeModal('m-simpan-kabel');
    loadAll(true);
  } catch(e) { showToast(e.message,'er'); }
}

// ──────────────── NODE & ITEM ACTIONS (DELETE, EDIT, COPY, MAPS, WA) ───

async function deleteNode(type, id) {
  showToast('Menghapus...', 'ok');
  try {
    var url = (type === 'item')
      ? `${BASE}/ftth/api/items/${id}`
      : `${BASE}/ftth/api/node/${type}/${id}`;
    await api('DELETE', url);
    showToast('Berhasil dihapus!', 'ok');
    loadAll(true);
  } catch (e) {
    showToast('Gagal menghapus: ' + e.message, 'er');
  }
}

async function deleteKabel(id) {
  showToast('Menghapus kabel...', 'ok');
  try {
    await api('DELETE', `${BASE}/ftth/api/kabel/${id}`);
    showToast('Kabel berhasil dihapus!', 'ok');
    loadAll(true);
  } catch (e) {
    showToast('Gagal menghapus kabel: ' + e.message, 'er');
  }
}

async function savePos(type, id, latlng) {
  showToast('Mengupdate posisi...', 'ok');
  try {
    if (type === 'item') {
      await api('POST', `${BASE}/ftth/api/items`, {
        id: id, latitude: latlng.lat, longitude: latlng.lng
      });
    } else {
      await api('POST', `${BASE}/ftth/api/update-pos`, {
        type: type, id: id, lat: latlng.lat, lng: latlng.lng
      });
    }
    showToast('Posisi berhasil diperbarui!', 'ok');
  } catch (e) {
    console.warn('Update pos warning:', e);
    showToast('Gagal update posisi: ' + (e.message || e), 'er');
  }
}

function copyCoords(lat, lng) {
  if (!lat || !lng) { showToast('Koordinat tidak valid', 'er'); return; }
  var text = `${lat},${lng}`;
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      showToast(`Koordinat disalin: ${text}`, 'ok');
    }).catch(() => {
      showToast(`Koordinat: ${text}`, 'ok');
    });
  } else {
    showToast(`Koordinat: ${text}`, 'ok');
  }
}

function openGoogleMaps(lat, lng) {
  if (!lat || !lng) { showToast('Koordinat tidak valid', 'er'); return; }
  window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
}

function sendWa(noWa, nama, status) {
  if (!noWa) {
    var pel = (DATA.pelanggan || []).find(p => p.nama === nama || p.nama_pelanggan === nama);
    if (pel && pel.no_wa) noWa = pel.no_wa;
  }
  if (!noWa) {
    showToast('Nomor WhatsApp belum diset untuk ' + (nama || 'pelanggan'), 'er');
    return;
  }
  var cleanNo = String(noWa).replace(/[^0-9]/g, '');
  if (cleanNo.startsWith('0')) cleanNo = '62' + cleanNo.slice(1);
  var msg = encodeURIComponent(`Halo Bpk/Ibu ${nama||'Pelanggan'},\n\nSistem FTTH Monitoring memberitahukan bahwa status koneksi internet Anda saat ini sedang: ${status || 'Gangguan / Offline'}.\nTim teknisi kami sedang melakukan pengecekan jalur jaringan. Terima kasih.`);
  window.open(`https://wa.me/${cleanNo}?text=${msg}`, '_blank');
}

function openWaModal() {
  openModal('m-wa-config');
}

function openEditNode(type, id) {
  var node = null;
  type = (type || '').toLowerCase();
  if (type === 'olt') node = DATA.olts.find(x => x.id == id);
  else if (type === 'item') node = DATA.items.find(x => x.id == id);
  else if (type === 'ont' || type === 'pelanggan') node = DATA.pelanggan.find(x => x.id == id);
  else node = DATA.odcOdps.find(x => x.id == id);

  if (!node) { showToast('Data node tidak ditemukan', 'er'); return; }

  document.getElementById('en-id').value = id;
  document.getElementById('en-type').value = type;
  document.getElementById('en-nama').value = node.nama || '';
  document.getElementById('en-lat').value = node.lat || node.latitude || '';
  document.getElementById('en-lng').value = node.lng || node.longitude || '';
  document.getElementById('en-catatan').value = node.deskripsi || node.catatan || node.lokasi || node.alamat || '';

  var extraFg = document.getElementById('en-extra-fg');
  var currentStatus = (node.status || 'offline').toLowerCase();
  var statusHtml = `<div class="fg"><label>Status Operasional</label><select id="en-status" style="width:100%;padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;background:#fff;"><option value="offline" ${currentStatus==='offline'?'selected':''}>🔴 OFFLINE (Mati / Putus)</option><option value="online" ${currentStatus==='online'?'selected':''}>🟢 ONLINE (Aktif)</option></select></div>`;

  if (type === 'olt') {
    extraFg.innerHTML = `
      <div class="fg"><label>IP Address</label><input id="en-ip" type="text" value="${node.ip_address||''}"></div>
      <div class="fg"><label>Kapasitas PON</label><input id="en-pon" type="number" value="${node.kapasitas_pon||16}"></div>
      ${statusHtml}`;
  } else if (type === 'odc') {
    extraFg.innerHTML = `<div class="fg"><label>Kapasitas Core</label><input id="en-core" type="number" value="${node.kapasitas_core||48}"></div>${statusHtml}`;
  } else if (type === 'odp') {
    extraFg.innerHTML = `<div class="fg"><label>Kapasitas Port</label><input id="en-port" type="number" value="${node.kapasitas_port||16}"></div>${statusHtml}`;
  } else if (type === 'ont' || type === 'pelanggan') {
    extraFg.innerHTML = `
      <div class="fg"><label>Serial ONT / MAC</label><input id="en-serial" type="text" value="${node.serial_ont||''}"></div>
      <div class="fg"><label>IP Address ONT</label><input id="en-ip" type="text" value="${node.ip_address||''}"></div>
      ${statusHtml}`;
  } else if (type === 'item') {
    var curCat = node.kategori || 'tiang_tumpu';
    extraFg.innerHTML = `
      <div class="fg"><label>Kategori Item (Simbol Map)</label>
        <select id="en-kategori" style="width:100%;padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;background:#fff;">
          <option value="tiang_loop" ${curCat==='tiang_loop'?'selected':''}>⭕ Tiang Loop Fiber (Simbol 1 - Sketsa Kiri)</option>
          <option value="slack_loop" ${curCat==='slack_loop'?'selected':''}>🔄 Joint Closure Oval / Loop (Simbol 2 - Sketsa Tengah)</option>
          <option value="tiang_tumpu" ${curCat==='tiang_tumpu'?'selected':''}>📡 Tiang Tumpu T-Bar (Simbol 3 - Sketsa Kanan)</option>
          <option value="tiang_odp" ${curCat==='tiang_odp'?'selected':''}>🔗 Tiang ODP</option>
          <option value="tiang_odc" ${curCat==='tiang_odc'?'selected':''}>🌐 Tiang ODC</option>
          <option value="joint_closure" ${curCat==='joint_closure'?'selected':''}>🔌 Joint Closure Box</option>
          <option value="htb_ap" ${curCat==='htb_ap'?'selected':''}>📶 HTB & Access Point</option>
          <option value="server_router" ${curCat==='server_router'?'selected':''}>🖥️ Server / Core Router</option>
        </select>
      </div>
      ${statusHtml}`;
  } else {
    extraFg.innerHTML = statusHtml;
  }

  var titleEl = document.getElementById('en-title');
  if (titleEl) titleEl.innerHTML = `<i class="bx bx-edit"></i> Edit / Beri Label ${type.toUpperCase()} — ${node.nama || ''}`;

  openModal('m-edit-node');
}

async function submitUpdateNode() {
  var id = document.getElementById('en-id').value;
  var type = document.getElementById('en-type').value;
  var nama = document.getElementById('en-nama').value.trim();
  var lat = parseFloat(document.getElementById('en-lat').value);
  var lng = parseFloat(document.getElementById('en-lng').value);
  var catatan = document.getElementById('en-catatan')?.value?.trim() || '';

  if (!nama) { showToast('Nama tidak boleh kosong', 'er'); return; }

  showToast('Menyimpan perubahan...', 'ok');
  try {
    if (type === 'item') {
      var itemPayload = { id: parseInt(id), nama: nama, latitude: lat, longitude: lng, deskripsi: catatan };
      if (document.getElementById('en-status')) itemPayload.status = document.getElementById('en-status').value;
      if (document.getElementById('en-kategori')) itemPayload.kategori = document.getElementById('en-kategori').value;
      await api('POST', `${BASE}/ftth/api/items`, itemPayload);
    } else {
      var payload = { type: type, nama: nama, latitude: lat, longitude: lng, deskripsi: catatan, catatan: catatan };
      if (document.getElementById('en-ip')) payload.ip_address = document.getElementById('en-ip').value;
      if (document.getElementById('en-serial')) payload.serial_ont = document.getElementById('en-serial').value;
      if (document.getElementById('en-pon')) payload.kapasitas_pon = parseInt(document.getElementById('en-pon').value);
      if (document.getElementById('en-core')) payload.kapasitas_core = parseInt(document.getElementById('en-core').value);
      if (document.getElementById('en-port')) payload.kapasitas_port = parseInt(document.getElementById('en-port').value);
      if (document.getElementById('en-status')) payload.status = document.getElementById('en-status').value;

      await api('PUT', `${BASE}/ftth/api/node/${id}`, payload);
    }

    closeModal('m-edit-node');
    showToast(`Berhasil menyimpan label/keterangan "${nama}"!`, 'ok');
    loadAll(true);
  } catch(e) {
    showToast('Gagal update: ' + e.message, 'er');
  }
}

function openEditKabel(id) {
  var k = DATA.kabels.find(x => x.id == id);
  if (!k) return;
  window.editingCableId = k.id;
  document.getElementById('nk-label').value = k.label || '';
  document.getElementById('nk-tipe').value = k.tipe || 'distribusi';
  document.getElementById('nk-core').value = k.jumlah_core || 12;
  document.getElementById('nk-mon').value = k.monitoring_type || 'manual';
  document.getElementById('nk-cat').value = k.catatan || '';
  if (k.color) {
    var txt = document.getElementById('nk-color');
    var picker = document.getElementById('nk-color-picker');
    if (txt) txt.value = k.color;
    if (picker) picker.value = k.color;
  }
  window.pendingCablePts = k.geometry || [];
  openModal('m-simpan-kabel');

  var fromSel = document.getElementById('nk-from');
  var toSel = document.getElementById('nk-to');
  if (fromSel && k.from_type && k.from_id) {
    fromSel.value = `${k.from_type}_${k.from_id}`;
  }
  if (toSel && k.to_type && k.to_id) {
    toSel.value = `${k.to_type}_${k.to_id}`;
  }
  autoGenCableLabel();
}

function openEditPopup(id) {
  var p = DATA.pelanggan.find(x => x.id == id);
  if (!p) return;
  showToast(`Edit Pelanggan ${p.nama || id}`, 'ok');
  openChangeWifi(p.id, p.nama || '');
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
}

function startMeasure() {
  showToast('Fitur Ukur Jarak aktif: Klik titik-titik di peta untuk mengukur jarak.', 'ok');
  startDrawCable();
}

// ──────────────── WIFI / ONT ACTIONS ─────────────────────────────────
function openChangeWifi(id, nama) {
  activeWifiPelanggan = id;
  document.getElementById('wifi-modal-title').innerHTML = '<i class="bx bx-wifi"></i> Ganti WiFi — ' + nama;
  document.getElementById('wifi-ssid').value = '';
  document.getElementById('wifi-pass').value = '';
  document.getElementById('wifi-status-msg').textContent = '';
  openModal('m-wifi');
  fetch(`${BASE}/ftth/api/wifi-info/${id}`,{headers:{Accept:'application/json'}})
    .then(r=>r.json()).then(d=>{
      if (d.ssid) document.getElementById('wifi-ssid').value = d.ssid;
    }).catch(()=>{});
}

async function submitChangeWifi() {
  var ssid = document.getElementById('wifi-ssid').value.trim();
  var pass = document.getElementById('wifi-pass').value.trim();
  var msgEl = document.getElementById('wifi-status-msg');
  if (!ssid && !pass) { msgEl.textContent='Isi SSID atau password minimal.'; return; }
  msgEl.style.color = 'var(--yellow)'; msgEl.textContent = 'Mengirim ke GenieACS...';
  try {
    var res = await api('POST', `${BASE}/ftth/api/set-wifi/${activeWifiPelanggan}`, {ssid, password:pass});
    msgEl.style.color='var(--green)'; msgEl.textContent=res.message;
    showToast('WiFi berhasil diubah!','ok');
    setTimeout(()=>closeModal('m-wifi'),1800);
  } catch(e) { msgEl.style.color='var(--red)'; msgEl.textContent=e.message; }
}

async function rebootOnt(id, nama) {
  if (!confirm(`Reboot ONT "${nama}"?\nPerangkat akan restart ±30 detik.`)) return;
  showToast('Mengirim perintah reboot...','ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/reboot-ont/${id}`, {});
    showToast(res.message,'ok');
  } catch(e) { showToast(e.message,'er'); }
}

// ──────────────── LIVE TRAFFIC CHART ─────────────────────────────────
function initTrafficChart(id) {
  var canvas = document.getElementById('chart-'+id);
  if (!canvas) return;
  if (trafficCharts[id]) { trafficCharts[id].destroy(); delete trafficCharts[id]; }

  var node = DATA.pelanggan.find(p => p.id === id);
  var isOnline = node ? (node.status === 'online') : true;

  var labels = Array.from({length:15},(_,i)=>i);
  var txData = Array.from({length:15},()=> isOnline ? (Math.random()*400+100) : 0);
  var rxData = Array.from({length:15},()=> isOnline ? (Math.random()*80+20) : 0);

  trafficCharts[id] = new Chart(canvas, {
    type:'line',
    data:{
      labels,
      datasets:[
        {label:'TX',data:txData,borderColor:isOnline?'#2563eb':'#dc2626',backgroundColor:isOnline?'rgba(37,99,235,.15)':'rgba(220,38,38,.05)',borderWidth:1.5,pointRadius:0,fill:true,tension:.4},
        {label:'RX',data:rxData,borderColor:isOnline?'#16a34a':'#dc2626',backgroundColor:isOnline?'rgba(22,163,74,.1)':'rgba(220,38,38,.05)',borderWidth:1.5,pointRadius:0,fill:true,tension:.4},
      ]
    },
    options:{
      animation:false,responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{x:{display:false},y:{display:false,min:0,max:isOnline?undefined:100}}
    }
  });

  var tEl = document.getElementById('tr-val-'+id);
  if (!isOnline) {
    if (tEl) {
      tEl.textContent = 'TX: 0.0 B · RX: 0.0 B (BERHENTI / OFFLINE)';
      tEl.style.color = 'var(--red)';
    }
    return; // Completely stop animation timer when device is offline!
  }

  var ti = setInterval(()=>{
    if (!document.getElementById('chart-'+id)) { clearInterval(ti); return; }
    var tx = Math.random()*600+100;
    var rx = Math.random()*150+20;
    txData.shift(); txData.push(tx);
    rxData.shift(); rxData.push(rx);
    trafficCharts[id]?.update('none');
    if (tEl) tEl.textContent = `TX: ${tx.toFixed(1)} K · RX: ${rx.toFixed(1)} K`;
  }, 1500);
}

// ──────────────── PING TERMINAL ──────────────────────────────────────
function quickPing(ip, id) {
  document.getElementById('ping-ip').value = ip;
  openModal('m-ping');
  doPing();
}
function openPingTerminal() { openModal('m-ping'); }

async function doPing() {
  var ip = document.getElementById('ping-ip').value.trim();
  if (!ip) { addTermLine('Masukkan IP address terlebih dahulu.','err'); return; }
  var out = document.getElementById('terminal-out');
  out.innerHTML = '';
  addTermLine(`> ping ${ip}`,'');
  addTermLine('Menjalankan ping... harap tunggu.','warn');
  try {
    var res = await api('POST', `${BASE}/ftth/api/ping`, {ip_address:ip});
    var lines = res.output_lines || (res.raw || '').split('\n');
    if (Array.isArray(lines) && lines.length > 0) {
      lines.forEach(line => {
        if (!line.trim()) return;
        var cls = (line.toLowerCase().includes('timeout') || line.toLowerCase().includes('unreachable') || line.toLowerCase().includes('expired') || line.toLowerCase().includes('100% loss')) ? 'err' : '';
        addTermLine(line, cls);
      });
    }
    var isOk = (res.reachable !== undefined) ? res.reachable : res.online;
    addTermLine(isOk ? 'HOST ONLINE' : 'HOST OFFLINE / TIMEOUT', isOk ? '' : 'err');

    // Auto update map data & sidebar status
    if (typeof loadMapData === 'function') loadMapData();
  } catch(e) {
    addTermLine('HOST OFFLINE / TIMEOUT', 'err');
    if (typeof loadMapData === 'function') loadMapData();
  }
}

function addTermLine(txt, cls='') {
  var out = document.getElementById('terminal-out');
  var span = document.createElement('span');
  span.className = 't-line' + (cls ? ' '+cls : '');
  span.textContent = txt;
  out.appendChild(span);
  out.scrollTop = out.scrollHeight;
}
function clearTerminal() { document.getElementById('terminal-out').innerHTML = ''; }

// ──────────────── KALKULATOR REDAMAN ─────────────────────────────────
function openKalkulatorRedaman() { openModal('m-kalkulator'); }

async function hitungRedaman() {
  var payload = {
    panjang_kabel_m:  parseFloat(document.getElementById('k-panjang').value)||1000,
    rasio_splitter:   parseInt(document.getElementById('k-splitter').value)||8,
    jumlah_splitter:  parseInt(document.getElementById('k-jml-splitter').value)||2,
    tx_power_dbm:     parseFloat(document.getElementById('k-tx').value)||2,
    jumlah_konektor:  parseInt(document.getElementById('k-kon').value)||4,
    jumlah_splice:    parseInt(document.getElementById('k-splice').value)||4,
  };
  try {
    var r = await api('POST',`${BASE}/ftth/api/redaman-calc`,payload);
    var rxCls = r.status==='ok'?'rx-ok':r.status==='warning'?'rx-wa':'rx-cr';
    document.getElementById('kalk-result').style.display='block';
    document.getElementById('kalk-result').innerHTML = `
      <div class="calc-row"><span>Fiber Loss (${(payload.panjang_kabel_m/1000).toFixed(2)}km × 0.35)</span><span>${r.breakdown.fiber} dB</span></div>
      <div class="calc-row"><span>Splitter Loss (${payload.jumlah_splitter}× 1:${payload.rasio_splitter})</span><span>${r.breakdown.splitter} dB</span></div>
      <div class="calc-row"><span>Konektor Loss</span><span>${r.breakdown.konektor} dB</span></div>
      <div class="calc-row"><span>Splice Loss</span><span>${r.breakdown.splice} dB</span></div>
      <div class="calc-row total"><span>Total Redaman</span><span>${r.total_loss_db} dB</span></div>
      <div class="calc-row"><span>TX Power OLT</span><span>+${r.tx_power} dBm</span></div>
      <div class="calc-row ${rxCls} total"><span>Estimasi RX di ONU</span><span>${r.rx_estimasi} dBm</span></div>
      <div style="margin-top:8px;font-size:10px;color:var(--muted);">${r.rekomendasi||''}</div>`;
  } catch(e) { showToast('Error: '+e.message,'er'); }
}

// ──────────────── TABEL ONU ──────────────────────────────────────────
async function openTabelOnu() {
  openModal('m-tabel');
  try {
    var res = await fetch(`${BASE}/ftth/api/data-table`,{headers:{Accept:'application/json'}});
    var data = await res.json();
    renderDataTable(data.data || data || []);
  } catch(e) { showToast('Gagal muat tabel: '+e.message,'er'); }
}

function renderDataTable(rows) {
  var tbody = document.getElementById('tbl-body');
  tbody.innerHTML = rows.map(r => `
    <tr>
      <td>${r.kode||'—'}</td>
      <td>${r.nama||'—'}</td>
      <td><code style="font-size:10px;">${r.ip||'—'}</code></td>
      <td>${r.odp||'—'}</td>
      <td>${r.odc||'—'}</td>
      <td>${r.olt||'—'}</td>
      <td><span style="color:${r.status==='online'?'var(--green)':r.status==='offline'?'var(--red)':'var(--yellow)'};font-weight:600;">${(r.status||'online').toUpperCase()}</span></td>
      <td><code style="font-size:10px;">${r.serial_ont||'—'}</code></td>
      <td>${r.onu_rx_power ? r.onu_rx_power+' dBm' : '—'}</td>
      <td><button onclick="copyCoords(${r.lat},${r.lng})" style="background:none;border:none;color:var(--acc);cursor:pointer;font-size:10px;"><i class="bx bx-copy"></i> ${r.lat ? r.lat+','+r.lng : '—'}</button></td>
    </tr>`).join('');
  document.getElementById('tbl-info').textContent = `Menampilkan ${rows.length} data`;

  document.getElementById('tbl-search').oninput = function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#tbl-body tr').forEach(tr => {
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  };
}

// ──────────────── DUPLIKAT PELANGGAN ────────────────────────────────
async function duplikatPelanggan(id, nama) {
  if (!confirm(`Duplikat pelanggan "${nama}"?\nIP akan di-reset.`)) return;
  showToast('Menduplikat...','ok');
  try {
    var res = await api('POST', `${BASE}/ftth/api/duplicate-pelanggan/${id}`, {});
    showToast(`Duplikat berhasil! ID baru: ${res.new_id}`,'ok');
    loadAll(true);
  } catch(e) { showToast(e.message,'er'); }
}

// ──────────────── SYNC BUTTONS ───────────────────────────────────────
async function syncGenieACS() {
  var btn = document.getElementById('btn-acs-do-sync');
  if (btn) { btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Syncing...'; btn.disabled = true; }
  try {
    var r = await api('POST', `${BASE}/ftth/api/sync-now`, {source:'genieacs'});
    showToast(`Sync selesai: ${r.results?.acs?.synced||0} ONT (${r.elapsed}s)`,'ok');
    var st = document.getElementById('st-sync'); if (st) st.textContent = 'Sync: ' + (r.synced_at||'');
    loadAll(true);
  } catch(e) { showToast('Sync gagal: '+e.message,'er'); }
  finally { if (btn) { btn.innerHTML = '<i class="bx bx-refresh"></i> Syncing with ACS...'; btn.disabled = false; } }
}

async function syncMikrotik() {
  var btn = document.getElementById('btn-sync-mt');
  btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Syncing...'; btn.disabled = true;
  showToast('Menjalankan Sync Mikrotik...','ok');
  try {
    await api('POST', `${BASE}/ftth/api/sync-now`, {source:'mikrotik'});
    showToast('Mikrotik Sync selesai','ok');
    loadAll(true);
  } catch(e) { showToast(e.message,'er'); }
  finally { btn.innerHTML = '<i class="bx bx-refresh"></i> Sync Mikrotik'; btn.disabled = false; }
}

// ──────────────── TELEGRAM SETTINGS ─────────────────────────────────
async function saveTelegramSettings() {
  var payload = {
    bot_token: document.getElementById('tg-token').value.trim(),
    chat_id:   document.getElementById('tg-chatid').value.trim(),
    enabled:   document.getElementById('tg-en').checked,
    notify_onu_offline: document.getElementById('tg-onu').checked,
    notify_odp_full:    document.getElementById('tg-odp').checked,
    notify_kabel_offline: document.getElementById('tg-kbl').checked,
    offline_threshold_minutes: parseInt(document.getElementById('tg-thresh').value)||5,
  };
  try {
    await api('POST',`${BASE}/ftth/settings/telegram`,payload);
    showToast('Pengaturan Telegram disimpan','ok');
    closeModal('m-telegram');
  } catch(e) { showToast(e.message,'er'); }
}

async function testTelegram() {
  try {
    var r = await api('POST',`${BASE}/ftth/settings/telegram/test`,{});
    showToast(r.message,'ok');
  } catch(e) { showToast(e.message,'er'); }
}

async function submitImportFile() {
  var fileInput = document.getElementById('import-file');
  if (!fileInput || !fileInput.files.length) {
    showToast('Pilih file CSV / KMZ terlebih dahulu','er');
    return;
  }
  showToast('Memproses file import KMZ / KML...','ok');
  var formData = new FormData();
  formData.append('kmz_file', fileInput.files[0]);
  formData.append('file', fileInput.files[0]);

  try {
    var res = await fetch(`${BASE}/ftth/import-kmz`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
      body: formData,
    });
    var json = await res.json();
    if (!res.ok || !json.success) throw new Error(json.message || 'Gagal import KMZ');
    showToast(json.message || 'Import KMZ berhasil! Data telah dimasukkan.','ok');
    closeModal('m-backup');
    loadAll(true);
  } catch(e) {
    showToast('Import Error: ' + e.message, 'er');
  }
}

// ──────────────── OFFLINE PANEL ──────────────────────────────────────
function renderOfflinePanel() {
  var offline = (DATA.pelanggan || []).filter(p => p.status === 'offline' || p.last_online_status == 0);
  offline.sort((a,b) => {
    var dateA = a.last_inform_at ? new Date(a.last_inform_at).getTime() : 0;
    var dateB = b.last_inform_at ? new Date(b.last_inform_at).getTime() : 0;
    return rpSort === 'terlama' ? (dateA - dateB) : (dateB - dateA);
  });
  var cnt = document.getElementById('offline-count'); if (cnt) cnt.textContent = offline.length;
  var listEl = document.getElementById('offline-list'); if (!listEl) return;
  if (!offline.length) {
    listEl.innerHTML = '<div style="padding:20px;text-align:center;color:var(--green);font-size:11px;"><i class="bx bx-check-circle" style="font-size:24px;display:block;margin-bottom:4px;"></i> Semua ONT Online</div>';
    return;
  }
  listEl.innerHTML = offline.map(p => `
    <div class="oi" onclick="flyToOnt(${p.id})">
      <div class="oi-dur">${offDuration(p.last_inform_at, true)}</div>
      <div class="oi-name">${String(p.nama||'Pelanggan')}</div>
      <div class="oi-ip">${String(p.kode||'')} (${String(p.ip_address||'—')})</div>
    </div>`).join('');
}

function setRpSort(s, btn) {
  rpSort = s;
  document.getElementById('rp-terlama')?.classList.toggle('active', s==='terlama');
  document.getElementById('rp-terbaru')?.classList.toggle('active', s==='terbaru');
  renderOfflinePanel();
}

function exportCsv() {
  if (!DATA || !DATA.pelanggan || !DATA.pelanggan.length) {
    showToast('Data pelanggan belum siap', 'er');
    return;
  }
  var headers = ["ID", "Kode Pelanggan", "Nama Pelanggan", "IP Address", "Status", "Latitude", "Longitude", "Inform Terakhir"];
  var csvRows = [headers.join(";")];
  DATA.pelanggan.forEach(p => {
    csvRows.push([
      p.id,
      `"${String(p.kode||'').replace(/"/g, '""')}"`,
      `"${String(p.nama||'').replace(/"/g, '""')}"`,
      `"${String(p.ip_address||'').replace(/"/g, '""')}"`,
      (p.status || 'offline').toUpperCase(),
      p.lat || '',
      p.lng || '',
      `"${String(p.last_inform_at||'').replace(/"/g, '""')}"`
    ].join(";"));
  });
  var csvContent = "\uFEFFsep=;\n" + csvRows.join("\r\n");
  var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  var url = URL.createObjectURL(blob);
  var link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", `FTTH_Data_Pelanggan_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  showToast('Export CSV berhasil (Kolom rapi di Excel)', 'ok');
}

function offDuration(dt, isOffline) {
  if (!dt) return '—';
  try {
    var diff = Math.floor((Date.now() - new Date(dt).getTime()) / 1000);
    if (diff < 0) diff = 0;
    if (!isOffline) {
      if (diff < 3600) return Math.floor(diff/60)+'m';
      return Math.floor(diff/3600)+'j '+Math.floor((diff%3600)/60)+'m';
    }
    if (diff < 60)    return diff+'d';
    if (diff < 3600)  return Math.floor(diff/60)+'m '+diff%60+'d';
    if (diff < 86400) return Math.floor(diff/3600)+'j '+Math.floor((diff%3600)/60)+'m';
    return Math.floor(diff/86400)+'hr '+Math.floor((diff%86400)/3600)+'j';
  } catch(e) { return '—'; }
}

// ──────────────── SIDEBAR ────────────────────────────────────────────
function renderSidebar() {
  renderList('tc-olt',   DATA.olts,  'olt',  'bx bx-server', n=>n.nama);
  renderList('tc-odc',   DATA.odcOdps.filter(n=>(n.tipe||'').toUpperCase()==='ODC'), 'odc', 'bx bx-cube-alt', n=>n.nama);
  renderList('tc-odp',   DATA.odcOdps.filter(n=>(n.tipe||'').toUpperCase()==='ODP'), 'odp', 'bx bx-box', n=>n.nama);
  renderList('tc-ont',   DATA.pelanggan, 'pelanggan', 'bx bx-home-alt', n=>String(n.kode||'')+' '+String(n.nama||''));
  renderKabelList();
  renderItemList();
}

function renderList(cid, items, type, iconClass, nameFn) {
  var el = document.getElementById(cid); if (!el) return;
  var addType = {'olt':'OLT','odc':'ODC','odp':'ODP','pelanggan':'ONT / Pelanggan','ont':'ONT'}[type];
  var hdr = addType ? `<div class="sec-hdr">${addType}<button class="add-btn" onclick="openAddPanel('${type}')"><i class="bx bx-plus"></i> Tambah</button></div>` : '';
  if (!items.length) {
    el.innerHTML = hdr + '<div style="padding:10px 8px;color:var(--muted);font-size:11px;">Belum ada data.</div>';
    return;
  }
  el.innerHTML = hdr + items.map(n => {
    var st = String(n.status || 'online');
    var sc = {online:'s-on',warning:'s-wa',offline:'s-of'}[st]||'s-on';
    return `<div class="ni" id="si-${type}-${n.id}" onclick="flyToNode('${type}',${n.id})">
      <i class="ni-icon ${iconClass}"></i>
      <div class="ni-name">${String(nameFn(n)||'—')}</div>
      <span class="ni-badge ${sc}">${st.toUpperCase()}</span>
    </div>`;
  }).join('');
}

function renderKabelList() {
  var el = document.getElementById('tc-kabel'); if (!el) return;
  var hdr = `<div class="sec-hdr">KABEL<div style="display:flex;gap:4px;"><button class="add-btn" onclick="openAutoTiangModal()"><i class="bx bx-map-pin"></i> Auto Tiang</button><button class="add-btn" onclick="startDrawCable()"><i class="bx bx-edit-alt"></i> Gambar</button></div></div>`;
  if (!DATA.kabels.length) { el.innerHTML = hdr+'<div style="padding:10px 8px;color:var(--muted);font-size:11px;">Belum ada kabel.</div>'; return; }
  el.innerHTML = hdr + DATA.kabels.map(k => {
    var st = String(k.status||'online');
    var sc = {online:'s-on',warning:'s-wa',offline:'s-of'}[st]||'s-on';
    return `<div class="ni" id="si-kabel-${k.id}" onclick="flyToKabel(${k.id})">
      <div class="ni-dot" style="background:${String(k.color||colorOf(st))};"></div>
      <div class="ni-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${String(k.label||'Kabel')}</div>
      <span class="ni-badge ${sc}" style="margin-right:6px;">${String(k.tipe||'').slice(0,3).toUpperCase()}</span>
      <div style="display:flex;gap:4px;align-items:center;">
        <button onclick="event.stopPropagation(); openEditKabel(${k.id})" style="background:none;border:none;color:#0284c7;cursor:pointer;font-size:14px;padding:2px;" title="Edit / Ubah Arah Kabel"><i class="bx bx-edit"></i></button>
        <button onclick="event.stopPropagation(); if(confirm('Hapus kabel ${k.label}?')) deleteKabel(${k.id})" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:14px;padding:2px;" title="Hapus Kabel"><i class="bx bx-trash"></i></button>
      </div>
    </div>`;
  }).join('');
}

function quickAddSymbol(kategori) {
  var names = {
    'tiang_loop': 'Tiang Loop Fiber',
    'slack_loop': 'Joint Closure Oval',
    'tiang_tumpu': 'Tiang Tumpu T-Bar'
  };
  var defaultName = names[kategori] || 'Item';
  var nextNum = (DATA.items || []).filter(i => i.kategori === kategori).length + 1;
  var fullDefault = `${defaultName} #${nextNum}`;
  
  var name = prompt(`Masukkan Nama / Kode untuk ${defaultName}:`, fullDefault);
  if (name === null) return;
  if (!name.trim()) name = fullDefault;

  showToast(`Modus Pasang Simbol: Klik titik mana saja di peta untuk menempatkan "${name}"...`, 'ok');
  
  if (MAP && MAP.getContainer()) MAP.getContainer().style.cursor = 'crosshair';
  
  MAP.once('click', async (e) => {
    if (MAP && MAP.getContainer()) MAP.getContainer().style.cursor = '';
    if (!e || !e.latlng) return;
    
    showToast(`Menyimpan "${name}" ke koordinat peta...`, 'ok');
    try {
      await api('POST', `${BASE}/ftth/api/items`, {
        nama: name,
        kategori: kategori,
        latitude: e.latlng.lat,
        longitude: e.latlng.lng,
        status: 'online',
        snmp_community: 'public'
      });
      showToast(`Berhasil menempatkan "${name}" pada peta!`, 'ok');
      loadAll(true);
    } catch(err) {
      showToast('Gagal menyimpan item: ' + err.message, 'er');
    }
  });
}

function renderItemList() {
  var el = document.getElementById('tc-item'); if (!el) return;
  var quickBar = `
    <div class="sec-hdr" style="background:#0f172a;color:#fff;">3 SIMBOL MANDIRI (KLIK & PASANG)</div>
    <div style="padding:8px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;flex-direction:column;gap:5px;">
      <button onclick="quickAddSymbol('tiang_loop')" style="display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:6px;border:1px solid #cbd5e1;background:#ffffff;cursor:pointer;font-size:11px;font-weight:700;color:#0f172a;transition:all .15s;">
        <svg width="18" height="24" viewBox="0 0 30 40" fill="none"><circle cx="15" cy="15" r="11" stroke="#000" stroke-width="2.5" fill="#fff"/><rect x="2" y="12" width="26" height="6" stroke="#000" stroke-width="2" fill="#fff"/><rect x="12" y="2" width="6" height="36" stroke="#000" stroke-width="2" fill="#fff"/></svg>
        <span style="flex:1;">+ Tiang Loop Fiber <small style="color:#64748b;display:block;font-weight:400;font-size:9px;">(Simbol 1 - Sketsa Kiri)</small></span>
      </button>

      <button onclick="quickAddSymbol('slack_loop')" style="display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:6px;border:1px solid #cbd5e1;background:#ffffff;cursor:pointer;font-size:11px;font-weight:700;color:#0f172a;transition:all .15s;">
        <svg width="22" height="16" viewBox="0 0 40 30" fill="none"><ellipse cx="20" cy="15" rx="17" ry="8" stroke="#000" stroke-width="2.5" fill="#fff"/><rect x="2" y="12" width="36" height="6" rx="2" stroke="#000" stroke-width="2" fill="#fff"/><rect x="16" y="3" width="8" height="24" rx="2" stroke="#000" stroke-width="2" fill="#fff"/></svg>
        <span style="flex:1;">+ Joint Closure Oval <small style="color:#64748b;display:block;font-weight:400;font-size:9px;">(Simbol 2 - Sketsa Tengah)</small></span>
      </button>

      <button onclick="quickAddSymbol('tiang_tumpu')" style="display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:6px;border:1px solid #cbd5e1;background:#ffffff;cursor:pointer;font-size:11px;font-weight:700;color:#0f172a;transition:all .15s;">
        <svg width="16" height="22" viewBox="0 0 24 34" fill="none"><line x1="2" y1="10" x2="22" y2="10" stroke="#000" stroke-width="3"/><line x1="12" y1="3" x2="12" y2="32" stroke="#000" stroke-width="3"/></svg>
        <span style="flex:1;">+ Tiang Tumpu T-Bar <small style="color:#64748b;display:block;font-weight:400;font-size:9px;">(Simbol 3 - Sketsa Kanan)</small></span>
      </button>
    </div>
    <div class="sec-hdr">DAFTAR ITEM TERPASANG (${DATA.items.length})</div>`;

  if (!DATA.items.length) {
    el.innerHTML = quickBar + '<div style="padding:10px 8px;color:var(--muted);font-size:11px;">Belum ada item terpasang. Klik tombol di atas untuk menambah.</div>';
    return;
  }

  el.innerHTML = quickBar + DATA.items.map(n => {
    var cat = String(n.kategori||'');
    var clr = ITEM_COLORS[cat]||'#2563eb';
    return `<div class="ni" id="si-item-${n.id}" onclick="flyToNode('item',${n.id})">
      <div class="ni-dot" style="background:${clr};"></div>
      <div class="ni-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${String(n.nama||'Item')}</div>
      <span class="ni-badge" style="font-size:9px;color:${clr};margin-right:4px;">${cat.replace('_',' ').toUpperCase()}</span>
      <div style="display:flex;gap:4px;align-items:center;">
        <button onclick="event.stopPropagation(); openEditNode('item',${n.id})" style="background:none;border:none;color:#0284c7;cursor:pointer;font-size:14px;padding:2px;" title="Edit Item"><i class="bx bx-edit"></i></button>
        <button onclick="event.stopPropagation(); if(confirm('Hapus item ${n.nama}?')) deleteNode('item',${n.id})" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:14px;padding:2px;" title="Hapus Item"><i class="bx bx-trash"></i></button>
      </div>
    </div>`;
  }).join('');
}

function switchTab(name, btn) {
  document.querySelectorAll('.tp').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  else {
    var el = document.querySelector(`.tp[data-tab="${name}"]`);
    if (el) el.classList.add('active');
  }
  document.querySelectorAll('.tc').forEach(t => t.classList.remove('active'));
  var tc = document.getElementById('tc-'+name);
  if (tc) tc.classList.add('active');
}
function switchTabByEl(name, btn) {
  switchTab(name, btn);
}

// ──────────────── FLY TO ────────────────────────────────────────────
function flyToNode(type, id) {
  var m = markerReg[type+'_'+id];
  if (m) { MAP.flyTo(m.getLatLng(),17,{duration:.7}); setTimeout(()=>m.openPopup(),750); }
}
function flyToOnt(id) { flyToNode('pelanggan', id); }
function flyToKabel(id) {
  var l = kabelReg[id];
  if (l) MAP.flyToBounds(l.getBounds(),{padding:[40,40],duration:.7});
}

function fitAll(instant) {
  var pts = [];
  [...DATA.olts,...DATA.odcOdps,...DATA.pelanggan].forEach(n => {
    if (n.lat && n.lng) pts.push([n.lat, n.lng]);
  });
  DATA.kabels.forEach(k => {
    if (k.geometry && k.geometry.length) {
      k.geometry.forEach(p => { if (p && p[0] && p[1]) pts.push([p[0], p[1]]); });
    }
  });
  if (!pts.length) { MAP.setView([-7.1207, 112.5959], 14); return; }
  if (instant) MAP.fitBounds(pts,{padding:[40,40]});
  else MAP.flyToBounds(pts,{padding:[40,40],duration:.8});
}

function hiSidebar(type, id) {
  document.querySelectorAll('.ni.sel').forEach(e=>e.classList.remove('sel'));
  var el = document.getElementById(`si-${type}-${id}`);
  if (el) { el.classList.add('sel'); el.scrollIntoView({block:'nearest',behavior:'smooth'}); }
}

// ──────────────── LAYER TOGGLES ──────────────────────────────────────
var lyrState = {olt:true,odc:true,odp:true,cable:true,ont:true,item:true};
function toggleLyr(name) {
  var lm = {olt:L_OLT,odc:L_ODC,odp:L_ODP,cable:L_CABLE,ont:L_ONT,item:L_ITEM};
  var l = lm[name]; if (!l) return;
  lyrState[name] = !lyrState[name];
  if (lyrState[name]) MAP.addLayer(l); else MAP.removeLayer(l);
  document.getElementById('mc-'+name)?.classList.toggle('on', lyrState[name]);
}
function toggleLabels() {
  showLabels = !showLabels;
  Object.values(labelReg).forEach(lbl => {
    showLabels ? L_LABEL.addLayer(lbl) : L_LABEL.removeLayer(lbl);
  });
  document.getElementById('mc-lbl')?.classList.toggle('on', showLabels);
  showToast(showLabels ? 'Label & jarak kabel ditampilkan' : 'Label disembunyikan','ok');
}
function toggleAnim() {
  animEnabled = !animEnabled;
  document.getElementById('mc-anim')?.classList.toggle('on', animEnabled);

  Object.values(kabelReg).forEach(line => {
    if (line._path) {
      if (animEnabled) {
        line._path.classList.add('animated-cable');
      } else {
        line._path.classList.remove('animated-cable');
      }
    }
  });

  showToast(animEnabled ? 'Animasi alur sinyal kabel diaktifkan ⚡' : 'Animasi kabel dinonaktifkan','ok');
}

function setMapMode(mode) {
  if (currentTile) MAP.removeLayer(currentTile);
  currentTile = {dark:darkTile,sat:satTile,osm:osmTile}[mode]||osmTile;
  currentTile.addTo(MAP);
}

// ──────────────── SEARCH ─────────────────────────────────────────────
function doSearch(q) {
  var sc = document.getElementById('s-clear'); if (sc) sc.style.display = q ? 'inline' : 'none';
  if (!q) { document.querySelectorAll('.ni').forEach(e=>e.style.display=''); return; }
  q = q.toLowerCase();
  var parts = q.split(',');
  if (parts.length === 2) {
    var lat = parseFloat(parts[0].trim()), lng = parseFloat(parts[1].trim());
    if (!isNaN(lat) && !isNaN(lng)) { MAP.flyTo([lat,lng],17,{duration:.7}); return; }
  }
  document.querySelectorAll('.ni').forEach(el => {
    var txt = el.querySelector('.ni-name')?.textContent?.toLowerCase()||'';
    el.style.display = txt.includes(q) ? '' : 'none';
  });
  var found = [...DATA.olts,...DATA.odcOdps,...DATA.pelanggan]
    .find(n=>(String(n.nama||'')+String(n.kode||'')+String(n.ip_address||'')).toLowerCase().includes(q));
  if (found) flyToNode(found.type||'pelanggan', found.id);
}
function clearSearch() { document.getElementById('search-input').value=''; doSearch(''); }

// ──────────────── STATUS BAR ──────────────────────────────────────────
function updateStatusBar() {
  var elOlt = document.getElementById('st-olt'); if (elOlt) elOlt.textContent = DATA.olts.length;
  var elOdc = document.getElementById('st-odc'); if (elOdc) elOdc.textContent = DATA.odcOdps.filter(n=>(n.tipe||'').toUpperCase()==='ODC').length;
  var elOdp = document.getElementById('st-odp'); if (elOdp) elOdp.textContent = DATA.odcOdps.filter(n=>(n.tipe||'').toUpperCase()==='ODP').length;
  var elKab = document.getElementById('st-kab'); if (elKab) elKab.textContent = DATA.kabels.length;
  var onCnt  = DATA.pelanggan.filter(p=>p.status==='online').length;
  var offCnt = DATA.pelanggan.filter(p=>p.status==='offline').length;
  var elOn = document.getElementById('st-on'); if (elOn) elOn.textContent = onCnt;
  var elOff = document.getElementById('st-off'); if (elOff) elOff.textContent = offCnt;
  var nb = document.getElementById('notif-badge');
  if (nb) {
    if (offCnt > 0) { nb.textContent=offCnt; nb.style.display='flex'; }
    else { nb.style.display='none'; }
  }
  var notifTxt = document.getElementById('notif-off-text');
  if (notifTxt) notifTxt.textContent = `${offCnt} ONT berstatus OFFLINE. Cek daftar di panel kanan.`;
}

// ──────────────── OPEN ADD PANEL (11 CATEGORIES & ONT SUPPORT) ─────────
function openAddPanel(type) {
  var targetTab = (type === 'item') ? 'item' : (type === 'ont' || type === 'pelanggan') ? 'ont' : type;
  switchTabByEl(targetTab);
  var el = document.getElementById('tc-' + targetTab); if (!el) return;
  var title = {olt:'OLT',odc:'ODC',odp:'ODP',ont:'ONT / Pelanggan',pelanggan:'ONT / Pelanggan',item:'Item Jaringan'}[type] || 'Perangkat';

  var odpOptions = (DATA.odcOdps || []).filter(n => (n.tipe||'').toUpperCase() === 'ODP').map(o => `
    <option value="${o.id}">${o.nama || 'ODP #'+o.id}</option>
  `).join('');

  var odcOptions = (DATA.odcOdps || []).filter(n => (n.tipe||'').toUpperCase() === 'ODC').map(o => `
    <option value="${o.id}">${o.nama || 'ODC #'+o.id}</option>
  `).join('');

  var extra = type==='olt'
    ? `<div class="fg"><label>IP Address</label><input id="an-ip" type="text" placeholder="192.168.1.1"></div>
       <div class="fg"><label>SNMP Community</label><input id="an-snmp" type="text" value="public"></div>
       <div class="fg"><label>Kapasitas PON</label><input id="an-pon" type="number" value="16"></div>`
    : type==='odc'
    ? `<div class="fg"><label>Kapasitas Core</label><input id="an-core" type="number" value="48"></div>`
    : (type==='ont' || type==='pelanggan')
    ? `<div class="fg"><label>ODP Induk</label>
         <select id="an-odp-id">
           <option value="">-- Pilih ODP (Opsional) --</option>
           ${odpOptions}
         </select>
       </div>
       <div class="fg"><label>Serial ONT / MAC (TR-069)</label><input id="an-serial" type="text" value="48575443A3F1A89D" placeholder="48575443A3F1A89D"></div>
       <div class="fg"><label>IP Address ONT</label><input id="an-ip" type="text" value="192.168.88.253" placeholder="192.168.88.253"></div>
       <div class="fg"><label>Nomor WhatsApp Pelanggan</label><input id="an-wa" type="text" placeholder="081234567890"></div>`
    : type==='item'
    ? `<div class="fg"><label>Kategori Item (11 Kategori)</label>
         <select id="an-cat">
           <option value="tiang_loop" selected>⭕ Tiang Loop Fiber (Simbol 1 - Gambar 2 Kiri)</option>
           <option value="slack_loop">🔄 Joint Closure Oval / Loop (Simbol 2 - Gambar 2 Tengah)</option>
           <option value="tiang_tumpu">📡 Tiang Tumpu T-Bar (Simbol 3 - Gambar 2 Kanan)</option>
           <option value="tiang_odp">🔗 Tiang ODP</option>
           <option value="tiang_odc">🌐 Tiang ODC</option>
           <option value="joint_closure">🔌 Joint Closure Box</option>
           <option value="htb_ap">📶 HTB & Access Point</option>
           <option value="server_router">🖥️ Server / Core Router</option>
           <option value="olt">OLT (Optical Line Terminal)</option>
           <option value="odc">ODC Cabinet</option>
           <option value="odp">ODP Box</option>
           <option value="ont">ONT (Customer Premises)</option>
           <option value="pelanggan">Pelanggan End-User</option>
         </select>
       </div>
       <div class="fg"><label>SNMP Community (Optional)</label><input id="an-snmp" type="text" value="public"></div>`
    : `<div class="fg"><label>Kapasitas Port</label><input id="an-port" type="number" value="16"></div>
       <div class="fg"><label>ODC Induk</label>
         <select id="an-odc-parent">
           <option value="">-- Tanpa ODC Induk / Direct --</option>
           ${odcOptions}
         </select>
       </div>`;

  el.innerHTML = `<div class="sec-hdr">TAMBAH ${title.toUpperCase()}</div>
  <div style="padding:8px;">
    <div class="fg"><label>Nama Pelanggan / Perangkat</label><input id="an-nama" type="text" placeholder="${title} baru..."></div>
    ${extra}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
      <div class="fg"><label>Latitude</label><input id="an-lat" type="number" step="any"></div>
      <div class="fg"><label>Longitude</label><input id="an-lng" type="number" step="any"></div>
    </div>
    <div style="font-size:10px;color:var(--acc);margin-bottom:8px;"><i class="bx bx-info-circle"></i> Klik lokasi di peta untuk mengisi koordinat</div>
    <div style="display:flex;gap:6px;">
      <button class="btn-p" style="flex:1;" onclick="submitAddNode('${type}')"><i class="bx bx-plus"></i> Simpan ${title}</button>
      <button class="btn-s" onclick="loadAll(true)">Batal</button>
    </div>
  </div>`;

  MAP.once('click', e => {
    var la=document.getElementById('an-lat'), ln=document.getElementById('an-lng');
    if (la && e.latlng) la.value=e.latlng.lat.toFixed(8);
    if (ln && e.latlng) ln.value=e.latlng.lng.toFixed(8);
    showToast('Koordinat terisi','ok');
  });
}

async function submitAddNode(type) {
  var catEl = document.getElementById('an-cat');
  if (catEl && catEl.value) {
    type = 'item';
  }
  var nama=document.getElementById('an-nama')?.value?.trim();
  var lat=parseFloat(document.getElementById('an-lat')?.value||'');
  var lng=parseFloat(document.getElementById('an-lng')?.value||'');
  if (!nama||!lat||!lng) { showToast('Nama dan koordinat wajib diisi','er'); return; }
  var payload = {nama,latitude:lat,longitude:lng};
  if (type==='olt') {
    payload.ip_address=document.getElementById('an-ip')?.value||'';
    payload.snmp_community=document.getElementById('an-snmp')?.value||'public';
    payload.kapasitas_pon=parseInt(document.getElementById('an-pon')?.value||16);
  } else if (type==='odc') {
    payload.tipe='ODC'; payload.kapasitas_core=parseInt(document.getElementById('an-core')?.value||48);
  } else if (type==='ont' || type==='pelanggan') {
    payload.tipe='ONT';
    payload.type='ont';
    var odpVal = document.getElementById('an-odp-id')?.value;
    if (odpVal) payload.odp_id = parseInt(odpVal);
    payload.serial_ont=document.getElementById('an-serial')?.value||'';
    payload.ip_address=document.getElementById('an-ip')?.value||'';
    payload.no_wa=document.getElementById('an-wa')?.value||'';
  } else if (type==='item') {
    payload.kategori=catEl?.value||'tiang_loop';
    payload.snmp_community=document.getElementById('an-snmp')?.value||'public';
    payload.status='online';
  } else {
    payload.tipe='ODP';
    payload.kapasitas_port=parseInt(document.getElementById('an-port')?.value||16);
    var parentVal = document.getElementById('an-odc-parent')?.value;
    if (parentVal) payload.parent_id = parseInt(parentVal);
  }
  var url = type==='olt' ? `${BASE}/ftth/api/olt` : type==='item' ? `${BASE}/ftth/api/items` : `${BASE}/ftth/api/node`;
  try {
    await api('POST',url,payload);
    var catLabel = catEl ? catEl.options[catEl.selectedIndex]?.text : nama;
    showToast(`Berhasil menambahkan: ${nama} (${catLabel||''})`,'ok');
    loadAll(true);
  } catch(e) { showToast(e.message,'er'); }
}

// ──────────────── API HELPER ──────────────────────────────────────────
async function api(method, url, body) {
  var opts = {method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}};
  if (body) opts.body = JSON.stringify(body);
  var res = await fetch(url,opts);
  var json = await res.json();
  if (!res.ok) throw new Error(json.message||`HTTP ${res.status}`);
  return json;
}

// ──────────────── TOAST ────────────────────────────────────────────────
var toastT;
function hideToast() {
  var el = document.getElementById('toast');
  if (el) {
    el.classList.remove('show');
    el.style.display = 'none';
    el.style.opacity = '0';
    el.style.visibility = 'hidden';
  }
}

function showToast(msg, type='ok') {
  var el = document.getElementById('toast');
  if (!el) return;
  var icon = type==='ok' ? '<i class="bx bx-check-circle"></i>' : '<i class="bx bx-error-circle"></i>';
  
  el.style.display = 'flex';
  el.style.opacity = '1';
  el.style.visibility = 'visible';
  el.style.pointerEvents = 'auto';
  
  el.innerHTML = `${icon} <span style="flex:1;">${msg}</span> <button type="button" onclick="hideToast(); event.stopPropagation();" style="background:none;border:none;color:inherit;cursor:pointer;font-size:18px;padding:2px 6px;margin-left:10px;line-height:1;display:inline-flex;align-items:center;justify-content:center;opacity:0.85;border-radius:4px;" title="Tutup Notifikasi"><i class="bx bx-x"></i></button>`;
  el.className = 'show ' + (type==='ok'?'ok':'er');
  
  clearTimeout(toastT);
  toastT = setTimeout(() => {
    hideToast();
  }, 2500);
}

// ──────────────── MODAL FULLSCREEN HELPER ─────────────────────────────
function toggleModalFullscreen(modalId) {
  var m = document.getElementById(modalId);
  var icon = document.getElementById('btn-fullscreen-icon');
  if (!m) return;
  if (m.classList.contains('fullscreen')) {
    m.classList.remove('fullscreen');
    if (icon) { icon.className = 'bx bx-fullscreen'; }
    showToast('Tampilan normal', 'ok');
  } else {
    m.classList.add('fullscreen');
    if (icon) { icon.className = 'bx bx-exit-fullscreen'; }
    showToast('Tampilan layar penuh', 'ok');
  }
}

// ──────────────── DRAGGABLE LEGEND ───────────────────────────────────
function makeLegendDraggable() {
  var leg = document.getElementById('legend');
  var hdr = document.getElementById('legend-hdr');
  if (!leg || !hdr) return;

  // Disable Leaflet's internal click/drag propagation on legend element!
  if (typeof L !== 'undefined' && L.DomEvent) {
    L.DomEvent.disableClickPropagation(leg);
    L.DomEvent.disableScrollPropagation(leg);
  }

  // Restore saved position from localStorage
  var savedPos = localStorage.getItem('ftth_legend_pos');
  if (savedPos) {
    try {
      var pos = JSON.parse(savedPos);
      if (pos.left !== undefined && pos.top !== undefined) {
        leg.style.top = pos.top + 'px';
        leg.style.left = pos.left + 'px';
        leg.style.bottom = 'auto';
        leg.style.right = 'auto';
      }
    } catch(e) {}
  }

  var isDragging = false;
  var startX = 0, startY = 0, initialLeft = 0, initialTop = 0;

  function startDrag(e) {
    if (e.target.closest('button')) return;
    isDragging = true;
    hdr.style.cursor = 'grabbing';

    var clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
    var clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);

    startX = clientX;
    startY = clientY;

    var rect = leg.getBoundingClientRect();
    var parentRect = (leg.offsetParent || document.body).getBoundingClientRect();
    initialLeft = rect.left - parentRect.left;
    initialTop = rect.top - parentRect.top;

    leg.style.bottom = 'auto';
    leg.style.right = 'auto';
    leg.style.left = initialLeft + 'px';
    leg.style.top = initialTop + 'px';

    if (e.cancelable) e.preventDefault();
    if (e.stopPropagation) e.stopPropagation();
  }

  function doDrag(e) {
    if (!isDragging) return;
    var clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
    var clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);

    var dx = clientX - startX;
    var dy = clientY - startY;

    var newLeft = initialLeft + dx;
    var newTop = initialTop + dy;

    var maxLeft = window.innerWidth - leg.offsetWidth - 10;
    var maxTop = window.innerHeight - leg.offsetHeight - 10;

    newLeft = Math.max(5, Math.min(newLeft, maxLeft));
    newTop = Math.max(5, Math.min(newTop, maxTop));

    leg.style.left = newLeft + 'px';
    leg.style.top = newTop + 'px';

    if (e.cancelable) e.preventDefault();
  }

  function stopDrag() {
    if (isDragging) {
      isDragging = false;
      hdr.style.cursor = 'grab';
      localStorage.setItem('ftth_legend_pos', JSON.stringify({
        left: parseInt(leg.style.left || 0),
        top: parseInt(leg.style.top || 0)
      }));
    }
  }

  hdr.addEventListener('mousedown', startDrag);
  document.addEventListener('mousemove', doDrag);
  document.addEventListener('mouseup', stopDrag);

  hdr.addEventListener('touchstart', startDrag, {passive:false});
  document.addEventListener('touchmove', doDrag, {passive:false});
  document.addEventListener('touchend', stopDrag);
}

function toggleLegendMin() {
  var content = document.getElementById('legend-content');
  var icon = document.getElementById('leg-min-icon');
  if (!content || !icon) return;
  var isHidden = content.style.display === 'none';
  content.style.display = isHidden ? 'block' : 'none';
  icon.className = isHidden ? 'bx bx-minus' : 'bx bx-plus';
}

// ──────────────── START ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initMap();
  makeLegendDraggable();
});
</script>
</body>
</html>
