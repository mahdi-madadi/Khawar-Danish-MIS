<?php
// Small local SVGs; no icon font, external library, or network request.
function uiIcon(string $name): string {
 $aliases=['login'=>'shield','today_timetable'=>'clock','blank_forms'=>'file','outstanding'=>'wallet','upcoming_exams'=>'pencil','low_attendance_alerts'=>'chart','backup_reminder'=>'database','recent_receipts'=>'file','create_charge'=>'wallet','new_teaching_group'=>'layers','teaching_groups'=>'layers','employee_profile'=>'briefcase','employee_salary_payments'=>'wallet','approved_employee_payroll'=>'clipboard','dashboard'=>'grid','students'=>'people','student'=>'person','teachers'=>'board','employees'=>'briefcase','employee'=>'briefcase','enrollments'=>'clipboard','programs'=>'book','subjects'=>'book','batches'=>'layers','rooms'=>'door','timetable'=>'clock','holidays'=>'calendar','forms'=>'file','attendance'=>'check','assessments'=>'pencil','results'=>'chart','certificates'=>'award','finance'=>'wallet','payroll'=>'wallet','employee_payroll'=>'briefcase','reports'=>'chart','users'=>'shield','settings'=>'sliders','backup'=>'database','audit'=>'history','new_record'=>'plus','register_student'=>'plus','new_enrollment'=>'plus','collect_payment'=>'wallet','edit'=>'pencil','profile'=>'person','save'=>'check','apply'=>'check','pay'=>'wallet','preview'=>'eye','load'=>'filter','logout'=>'exit','print'=>'printer','previous'=>'arrow','next'=>'arrow','today'=>'calendar','receipt'=>'file','employee_payslip'=>'file','open'=>'eye','cancel'=>'close'];
 $key=$aliases[$name]??$name;
 $paths=[
 'grid'=>'<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
 'people'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.9M15 3.1a4 4 0 0 1 0 7.8"/><circle cx="9" cy="7" r="4"/>',
 'person'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
 'board'=>'<rect x="3" y="3" width="18" height="13" rx="2"/><path d="M8 21l4-5 4 5M7 8h10m-10 4h6"/>',
 'briefcase'=>'<rect x="3" y="7" width="18" height="14" rx="3"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12a22 22 0 0 0 18 0m-9 0v4"/>',
 'clipboard'=>'<rect x="4" y="4" width="16" height="18" rx="3"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M8 11h8m-8 4h5"/>',
 'book'=>'<path d="M12 5C9 3 5 3 2 4v15c4-1 7 0 10 2 3-2 6-3 10-2V4c-3-1-7-1-10 1Zm0 0v16"/>',
 'layers'=>'<path d="m12 3 10 5-10 5L2 8l10-5Zm-10 9 10 5 10-5M2 16l10 5 10-5"/>',
 'door'=>'<path d="M3 21h18M6 21V5l12-2v18M6 5h12m-4 7v2"/>',
 'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h1m3 0h1m3 0h1"/>',
 'file'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8m-8 4h5"/>',
 'check'=>'<path d="m8 12 3 3 6-7"/><rect x="3" y="3" width="18" height="18" rx="5"/>',
 'pencil'=>'<path d="m16 3 5 5M4 15 17 2l5 5L9 20l-7 2 2-7Z"/>',
 'chart'=>'<path d="M4 3v18h17M8 16v-4m5 4V8m5 8V5"/>',
 'award'=>'<circle cx="12" cy="8" r="6"/><path d="m8 13-2 9 6-3 6 3-2-9"/>',
 'wallet'=>'<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 8V5a2 2 0 0 1 2-2h13m-1 8h4v6h-4a3 3 0 0 1 0-6Z"/>',
 'shield'=>'<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4Z"/><path d="m8 11 3 3 5-5"/>',
 'sliders'=>'<path d="M4 4v5m0 4v7M12 4v10m0 4v2m8-16v2m0 4v10M2 9h4m4 9h4m4-12h4"/>',
 'database'=>'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 4 18 4 18 0V5M3 12c0 4 18 4 18 0"/>',
 'history'=>'<path d="M3 10a9 9 0 1 1 2 8M3 3v7h7m2-4v6l4 2"/>',
 'plus'=>'<path d="M12 5v14M5 12h14"/>',
 'eye'=>'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
 'filter'=>'<path d="M3 4h18l-7 8v7l-4 2v-9L3 4Z"/>',
 'exit'=>'<path d="M9 21H4V3h5m5 4 5 5-5 5m-6-5h13"/>',
 'printer'=>'<path d="M6 9V3h12v6m-12 9H3V9h18v9h-3M6 14h12v7H6v-7Z"/>',
 'arrow'=>'<path d="m9 5 7 7-7 7"/>',
 'close'=>'<path d="m6 6 12 12M6 18 18 6"/>',
 ];
 return '<svg class="ui-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.($paths[$key]??$paths['clipboard']).'</svg>';
}
