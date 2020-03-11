# tabReborn

A lightweight, selfhosted web app that camps, resorts and schools can use as a simple POS.

:raised_hands: Thank you to [DataTables](https://datatables.net) for the fast and interactive tables.

*Note: Migrations are not up to date. I am manually editing my local MySQL tables and will update migrations when it is more stable.*

## Features (so far):

- User control
    - Three different access levels to choose from (Camper, Cashier and Administrator). Change each user's role anytime.
        - Cashiers can ring up orders
        - Administrators can create/edit/delete users, products, categories and settings. They can also view user history and see detailed information about every order.
- Parental control
    - Parents or guardians can set their children a certain amount of money to spend in each category.
    - These limits can be set per-category to either day or week.
- Product control and editing
    - Change the name and price anytime.
    - Optional PST charge on a per-item basis.
- Live preview of price (with taxes) on sidebar during every purchase. If the user does not have enough money the submit button will be disabled (Backend check as well though!).
- Return any order.
- Editable GST and PST percentages.
- Detailed user history and order list pages with fully interactive and searchable tables (see above!).

## Screenshots:

#### First Cashier Page
![First Cashier Page](https://images.tadhgboyle.dev/scrn135552.png)

#### Second Cashier Page
![Second Cashier Page](https://images.tadhgboyle.dev/scrn135623.png)

#### User Information Page
![User Info Page](https://images.tadhgboyle.dev/scrn135504.png)

#### Order List Page
![Order List Page](https://images.tadhgboyle.dev/scrn135734.png)

#### Order Information Page
![Order Info Page](https://images.tadhgboyle.dev/scrn135910.png)

## Roadmap:

*High to Low priority sort*
- Click on item name to check/uncheck checkbox. Makes it not as hard to click a small area
- Discounts? Check if purchaser is staff role and give % off? (Edit % in settings)
- Add sales/discounts to item for period of time (automatic or button)
- Stats: Income by week, month, most popular products etc
- Add stock/inventory features
- Add tracking of role/balance etc changes
- Bulk change prices of items (Everything 10% *off* or everything 20% *more* etc)
- Add Manager role.
    - All Cashier permissions + adding/editing products
- Merge index and orders page with ajax for moar speeeeeed.
- Add PDF printing of all users transactions
- Seperate orders, users and stats into weeks
- Allow user to change light/dark mode