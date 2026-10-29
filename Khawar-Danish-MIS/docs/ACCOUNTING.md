# Accounting rules and worked examples

All amounts use AFN with two decimals. Database money is DECIMAL; PHP converts money strings to integer hundredths for arithmetic. Percent is represented in hundredths of a percent. Rounding is half-up to the nearest hundredth. Teacher fixed amounts use a 30-day denominator and actual covered Gregorian calendar days within the agreement. These rules are implemented in source but need runtime acceptance.

## Charges are not receipts

Student owes tuition 1,500. A 100 scholarship creates a credit, leaving 1,400 due. A 1,000 receipt allocates 1,000 to the charge, leaving 400 due. Cash increased by 1,000; it did not increase when the charge or scholarship was created.

The oldest outstanding charge is allocated first, ordered by due date then charge ID, across the student's enrollments. Registration fees can receive allocations, but they are excluded from teacher percentage. If cash arrives before charges exist, it stays an unallocated advance. Use “Apply existing advance” after adding charges. Allocations are immutable; existing allocation rows are never silently increased after payroll approval.

## Refunds and credits are different

Refund 200 from the 1,000 tuition allocation. Cash falls by 200. The unpaid tuition balance becomes 600. If the student no longer owes that 200, separately create an authorized 200 credit with a reason; due then returns to 400. A refund alone does not erase the underlying fee obligation.

To refund advance, leave the allocation number empty. Refunds cannot exceed the specific allocation's remaining amount or the unallocated advance. Each refund has its own reference token. Repeated submission of the same token is ignored; a newly opened form is a new intentional transaction.

## Teacher compensation

Class agreement: fixed 1,000 per 30 calendar days plus 20% of eligible tuition collected. Of a 1,000 receipt, 900 paid tuition and 100 paid registration. Percentage is 180, not 200. For 30 covered days, approved earning = 1,180; for 31 covered days it is 1,213.33. No cash expense is recognized until salary is paid.

Shared teachers: A receives 20%, B receives 30% for the same class. Each has a distinct agreement. The combined active share cannot exceed 100%. A teacher teaching multiple subjects in one class should have one class agreement, not duplicate agreements. A teacher teaching multiple classes needs the fixed salary split between those agreements manually.

Only tuition **allocations** with receipt dates inside an agreement qualify. Unallocated advances do not qualify. Discounts lower charges and therefore the amount eventually collected; they are not separate negative cash transactions. Each agreement/allocation is recorded once in payroll_sources. Approved payroll has an immutable calculation snapshot.

A later refund produces a negative percentage adjustment in the next unapproved payroll period that includes it. Previously approved payroll is unchanged. Cumulative rounding of refund adjustments avoids repeatedly rounding tiny fragments independently. Test partial refunds, full refunds, multiple refund dates, allocation timing and old-receipt backdating before production use.

Fixed amount 3,100 for January; agreement active January 17–31 inclusive: 15 / 31 × 3,100 = 1,500. Fixed amounts per agreement are rounded separately. Payroll months use **Gregorian** dates even when the interface date entry is Shamsi. A month cannot be approved until it ends. Old or already approved periods cannot be reapproved.

Negative adjustments can make a month's earning negative. Payments are capped by both that payroll record's outstanding total and the teacher's net approved balance. Recovery of previously overpaid amounts has no dedicated workflow in 0.1.

## Cash summary

Cash-basis surplus/deficit = student receipts − refunds + other income − general expenses − teacher payroll payments.

- Opening balances are excluded.
- Transfers between cash/bank accounts are excluded.
- Unpaid student charges and unpaid teacher earnings are excluded.
- Teacher salary paid through Payroll must not also be entered as a general expense.
- A general movement reversal is a new opposite-signed movement dated when the correction is recorded, linked to the original. The original is unchanged.
- Account balance is opening balance plus account-specific inflows minus outflows. It is not “profit”.

## مثال کوتاه به دری

فیس ۱۵۰۰ افغانی، تخفیف ۱۰۰ و پرداخت ۱۰۰۰: باقی‌مانده ۴۰۰ افغانی است. پول نقد فقط ۱۰۰۰ زیاد شده است. اگر ۲۰۰ افغانی بازپرداخت شود، پول نقد ۲۰۰ کم و باقی‌ماندهٔ فیس ۶۰۰ می‌شود. اگر آن ۲۰۰ دیگر قابل مطالبه نیست، کاهش فیس را جدا با دلیل ثبت کنید.

سهم استاد ۲۰٪ از فیس دریافت‌شده است؛ فیس ثبت‌نام شامل سهم نیست. معاش تأییدشده تا زمان پرداخت، مصرف نقدی حساب نمی‌شود. انتقال پول از صندوق به بانک عاید یا مصرف نیست.


Schedule, attendance, student activity and teacher salary update (29 October 2026)

Identical subject/time/room/date ranges display once with all teaching weekdays. Daily conflict validation is preserved. Monthly attendance and paper sheets show each weekday beside its day number.
Issued completion and appreciation certificates also include English recognition text. Names retain their stored spelling.
Dashboard active/inactive counts and expandable full lists are derived on every visit. Active means an administratively active student with an active enrollment in an active batch covering today; both enrollment and batch dates apply. Ended courses automatically cease qualifying; renewal in a current course restores activity. Payment alone does not establish activity. New students without a current enrollment and future-only enrollments are inactive for now.
Teacher fixed salary uses agreed amount / 30 × covered calendar days, rounded to cents. A 3000 AFN amount yields 2800 for 28 days and 3100 for 31 days. Partial contracts count only covered days. Previously approved payroll remains unchanged. Payroll periods use Gregorian months. Administrative employee payroll is unchanged.
