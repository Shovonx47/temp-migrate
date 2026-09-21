# SMS Gateway Dashboard - Feature Specifications

## 1. UI/UX Requirements
*   **Theme:** Modern enterprise dark dashboard with responsive cards.
*   **Updates:** Live auto-refresh every 5 to 10 seconds with real-time counters.

## 2. Live SMS Traffic Monitoring
*   **SMS Metrics:** Sent Today, Delivered *(existing)*, Failed *(existing)*, Pending *(existing)*, Processing, Retry Queue, Expired, DLR Received, DLR Pending.
*   **Throughput:** SMS/Second, SMS/Minute, SMS/Hour, Queue Size, Delivery Rate *(existing)*.
*   **Content:** SMS Content.
*   **Gateway Metrics:** Active Gateways *(existing)*, Gateway Queue, Gateway Response Time, Gateway Wallet *(existing)*, Gateway Status *(existing)*, Gateway Balance Showing.
*   **Operator Traffic:** Operator Wise Traffic (GP, Banglalink, Robi, Airtel, Teletalk).

## 3. System Monitoring
*   Server Uptime
*   Queue Workers

## 4. Analytics
*   Failed Reason Summary
*   DLR Summary
*   Top Operators
*   Top Gateways *(existing)*

## 5. Profit Calculation & Financials
**Requirement:** Fully automated Profit Calculation System. No manual input or calculation is allowed. All values must be calculated dynamically from message records, customer selling rates, and gateway buying rates.

### Calculation Formulas
*   **Revenue** = Total SMS × Customer Selling Rate
*   **Cost** = Total SMS × Gateway Buying Rate
*   **Profit** = Revenue - Cost
*   **Profit Margin (%)** = ((Revenue - Cost) / Revenue) × 100

### Dashboard Financial Cards (Revenue, Cost, Profit)
*   **Daily:** Today, Yesterday
*   **Weekly:** This Week
*   **Monthly:** This Month
*   **Yearly:** This Year
*   **Overall:** Total Revenue, Total Cost, Total Profit

### Auto-Calculated Analytics
*   Average Selling Price per SMS
*   Average Buying Price per SMS
*   Average Profit per SMS
*   Profit Margin (%)

## 6. Data Sources & Filters
### Data Sources
*   SMS Message Records
*   Gateway Buying Rate
*   Message Delivery Records
*   Billing Records

### Filters
*   Today, Yesterday, Last 7 Days, Last 30 Days, This Month, Last Month, This Year, Custom Date Range.
*   *Note: All dashboard values and charts must refresh automatically based on the selected date range.*

## 7. Reports & Data Export
### Reports Section
*   Failed SMS Report
*   Gateway Error Logs
*   API Error Logs
*   Live Alerts

### Export Requirements (Excel, CSV, PDF)
*   Must include: Revenue, Cost *(existing)*, Profit, Profit Margin, Average Selling Price, Average Buying Price, Average Profit.
*   *Note: Fix existing export features that are currently not working.*

## 8. Performance Requirements
*   Real-time calculation.
*   Optimized database queries and fast aggregation for large datasets.
*   Accurate financial reporting.

## 9. Security & Access Control
*   Employee Access Permission System.
