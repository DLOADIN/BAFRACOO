E-Commerce System Documentation
Complete System with Three Core Features
System Overview
This e-commerce system implements three core features:
1.Item ID × FIFO - Inventory management with First In, First Out
2.Purchase Price | Sale Price - Two-price system for profit tracking
3.Ordering Multiple Items (Cart) - Multi-item shopping cart system
Feature 1: Item ID × FIFO
FIFO (First In, First Out) means the oldest stock is sold first. This requires tracking multiple stock batches for each item.
1.1 Database Tables
ITEMS Table (Product Catalog)
Contains all products available in the shop. Customers always see ALL items.
Field	Type	Description	Key
ID	Integer/String	Unique identifier for each item	PRIMARY
Name	String	Product name	
Description	Text	Detailed product description	
Image	String/File	Product image	
STOCK Table (FIFO Batches)
Important: Stock ID is REQUIRED for FIFO. Multiple stock batches can exist for the same item, each with its own Record Date. The oldest batch (earliest Record Date) is sold first.
Field	Type	Description	Key	FIFO Role
Stock ID	Integer	Unique batch ID	PRIMARY KEY	Identifies each batch
Item ID	Integer	Links to ITEMS	FOREIGN KEY	Which item
Quantity	Integer	Available units		Decreases on sale
Record Date	Date	When added		FIFO ORDER KEY
1.2 How FIFO Works
Example Stock for Item 01 (Selection):
Stock ID	Item ID	Quantity	Record Date	Order
S001	01	50	2026-01-10	1st (Oldest)
S002	01	100	2026-02-05	2nd
S003	01	75	2026-02-15	3rd (Newest)
Customer Orders 120 Units:
•System takes 50 units from S001 (oldest batch)
•System takes 70 units from S002 (next oldest)
•S003 remains untouched (still newest)
Feature 2: Purchase Price | Sale Price
Each stock batch must have TWO separate prices to track profit and support proper accounting.
2.1 Updated STOCK Table (with Two Prices)
Stock ID	Item ID	Quantity	Record Date	Purchase Price	Sale Price
S001	01	50	2026-01-10	10,000 RWF	15,000 RWF
S002	01	100	2026-02-05	11,000 RWF	15,500 RWF
2.2 The Two Prices Explained
Purchase Price (Cost Price)
The price we buy the product from the supplier.
Example: We buy a shoe from supplier at 10,000 RWF - this is the Purchase Price.
Sale Price (Selling Price)
The price we sell the product to the customer.
Example: We sell the same shoe at 15,000 RWF - this is the Sale Price.
2.3 Profit Calculation
Profit = Sale Price - Purchase Price
Example: 15,000 RWF - 10,000 RWF = 5,000 RWF profit per unit
2.4 Benefits
•Calculate profit per product
•Generate sales reports
•Track business performance
•Support proper accounting
Feature 3: Ordering Multiple Items (Cart)
NOTE: This feature is implemented AFTER Features 1 and 2 are complete.
This feature allows customers to select multiple items at once, specify quantities for each, and add them all to the cart with a single action.
3.1 Customer Interface
Step 1: Customer Sees All Available Items
When a customer enters the shop, they see the complete catalog of all items with:
•Item image and name (from ITEMS table)
•Item description (from ITEMS table)
•Sale price (from STOCK table - oldest batch for FIFO)
•Total available quantity (sum of all batches from STOCK table)
Step 2: Select Multiple Items
Customer can select several items at once:
Item Selection	Quantity Input	Unit Price	Available
[Dropdown] Selection	[Input Box] 50	15,000 RWF	225
[Dropdown] Cement	[Input Box] 100	12,500 RWF	500
[Dropdown] Pipe	[Input Box] 30	10,000 RWF	500
[+ Add More Items]  [Add to Cart]
Step 3: Shopping Cart Display
After clicking 'Add to Cart', all selected items appear in the cart:
Item Name	Unit Price	Quantity	Total
Selection	15,000 RWF	50	750,000 RWF
Cement	12,500 RWF	100	1,250,000 RWF
Pipe	10,000 RWF	30	300,000 RWF
		GRAND TOTAL:	2,300,000 RWF
3.2 Backend Processing with FIFO
When the order is submitted, the system:
4.For each item in the cart, queries stock batches ordered by Record Date (oldest first)
5.Deducts quantity from oldest batches first (FIFO)
6.Records which stock batches were used in ORDER_ITEMS table
7.Tracks both Purchase Price and Sale Price for profit calculation
3.3 Key Benefits
•Efficiency: Customer selects all items at once instead of one-by-one
•Better UX: Mirrors real shopping experience - fill cart, then checkout
•Bulk Orders: Perfect for B2B or wholesale customers
•Accurate Tracking: FIFO ensures oldest stock is used, profit is correctly calculated
Implementation Summary
Phase 1: Implement Feature 1 (FIFO)
•Create ITEMS and STOCK tables
•Stock ID is required
•Implement FIFO logic (order by Record Date)
Phase 2: Add Feature 2 (Two-Price System)
•Add Purchase Price field to STOCK table
•Add Sale Price field to STOCK table
•Implement profit calculation (Sale Price - Purchase Price)
Phase 3: Build Feature 3 (Multi-Item Cart)
•Create customer interface for selecting multiple items
•Build cart display with totals
•Integrate FIFO stock deduction
•Track orders in ORDER_ITEMS with stock batch references
Final System Features:
•✓ FIFO inventory management (oldest stock sold first)
•✓ Dual pricing (Purchase Price + Sale Price for profit tracking)
•✓ Multi-item cart (select multiple items, add all at once)
•✓ Complete item catalog visible to customers
•✓ Accurate profit calculation per order
•✓ Proper accounting and financial reporting support