const route = typeof window !== 'undefined' ? window.route : () => '#';

const Menu = [
    {
        path: route('dashboard'),
        icon: 'solar:laptop-bold-duotone',
        title: 'Dashboard',
        name: 'dashboard',
        always: true,
    },

    {
        path: '/catalog',
        icon: 'solar:book-2-bold-duotone',
        title: 'Catalog',
        name: 'catalog',
        children: [
            {
                path: route('catalog.brands.index'),
                title: 'Brands',
                name: 'catalog.brands.index',
            },
            {
                path: route('catalog.finish.index'),
                title: 'Finish',
                name: 'catalog.finish.index',
            },
            {
                path: route('catalog.products.index'),
                title: 'Products',
                name: 'catalog.products.index',
            },
        ],
    },
    {
        path: '/sales',
        icon: 'solar:clipboard-list-bold-duotone',
        title: 'Sales',
        name: 'sales',
        children: [
            {
                path: route('sales.customers.index'),
                title: 'Add Customers',
                name: 'sales.customers.index',
            },
            {
                path: route('sales.orders.index'),
                title: 'Sales Invoices',
                name: 'sales.orders.index',
            },
            {
                path: route('sales.returns.index'),
                title: 'Sales Returns',
                name: 'sales.returns.index',
            },
            // {path: route('sales.sales-by-suit'), title: 'Sales By Suits', name: 'sales.sales-by-suit'},
            {
                path: route('widgets.average-sale-meter'),
                title: 'Sales Widgets',
                name: 'widgets.average-sale-meter',
            },
        ],
    },
    {
        path: '/purchase',
        icon: 'solar:inbox-in-bold-duotone',
        title: 'Purchases',
        name: 'purchases',
        children: [
            {
                path: route('purchases.suppliers.index'),
                title: 'Suppliers',
                name: 'purchases.suppliers.index',
            },
            {
                path: route('purchases.fabric-receivings.index'),
                title: 'Fabric Receiving',
                name: 'purchases.fabric-receivings.index',
            },
            {
                path: route('purchases.pos.index'),
                title: 'Fabric Purchases',
                name: 'purchases.pos.index',
            },
            {
                path: route('purchases.por.index'),
                title: 'Fabric Returns',
                name: 'purchases.por.index',
            },
        ],
    },
    {
        path: '/stock',
        icon: 'solar:clipboard-list-bold',
        title: 'Stock',
        name: 'stocks',
        children: [
            {
                path: route('stocks.inventories.index'),
                title: 'Inventory',
                name: 'stocks.inventories.index',
            },
            {
                path: route('stocks.conversions.index'),
                title: 'Conversion',
                name: 'stocks.conversions.index',
            },
            {
                path: route('stocks.value-addition.index'),
                title: 'Value Addition',
                name: 'stocks.value-addition.index',
            },
            {
                path: route('stocks.value-by-brand'),
                title: 'Value By Brand',
                name: 'stocks.value-by-brand',
            },
            {
                path: route('stocks.product-history'),
                title: 'Product History',
                name: 'stocks.product-history',
            },
            {
                path: route('stocks.store-transfers.index'),
                title: 'Store Transfers',
                name: 'stocks.store-transfers.index',
            },
        ],
    },
    {
        path: '/accounts',
        icon: 'solar:notebook-bold-duotone',
        title: 'Accounts',
        name: 'accounts',
        children: [
            {
                path: route('accounts.accounts.index'),
                title: 'Accounts',
                name: 'accounts.accounts.index',
            },
            {
                path: route('accounts.journals.index'),
                title: 'Journals',
                name: 'accounts.journals.index',
            },

            /*{
                path: route('accounts.journal-voucher.index'),
                title: 'Journal Voucher',
                name: 'accounts.journal-voucher.index'
            },
            {path: route('accounts.payments.index'), title: 'Payments', name: 'accounts.payments.index'},
            {path: route('accounts.receipts.index'), title: 'Receipts', name: 'accounts.receipts.index'},*/
            {
                path: route('accounts.ledgers.index'),
                title: 'Ledgers',
                name: 'accounts.ledgers.index',
            },
            {
                path: route('accounts.cash-bank.summary'),
                title: 'Cash Bank Summary',
                name: 'accounts.cash-bank.summary',
            },
            {
                path: route('accounts.income-statement'),
                title: 'Income Statement',
                name: 'accounts.income-statement',
            },
            {
                path: route('accounts.balance-history'),
                title: 'Balance History',
                name: 'accounts.balance-history',
            },
            {
                path: route('accounts.sale-summary'),
                title: 'Sales Summary',
                name: 'accounts.sale-summary',
            },
        ],
    },
    {
        path: '/reports',
        icon: 'solar:chart-bold-duotone',
        title: 'Reports',
        name: 'reports',
        children: [
            //{ path: route("reports.graph.index"), title: "Graph", name: "reports.graph.index" },
            {
                path: route('reports.po.purchases'),
                title: 'Purchases',
                name: 'reports.po.purchases',
            },
            {
                path: route('reports.fast-selling.products'),
                title: 'Fast Selling Products',
                name: 'reports.fast-selling.products',
            },
            {
                path: route('reports.purchased-date.products'),
                title: 'Products by Purchase Date',
                name: 'reports.purchased-date.products',
            },
            {
                path: route('reports.summary'),
                title: 'Daily Summary',
                name: 'reports.summary',
            },
            {
                path: route('reports.in.out.transactions.summary'),
                title: 'In/Out Transaction Summary',
                name: 'reports.in.out.transactions.summary',
            },
            {
                path: route('reports.sales-daily'),
                title: 'Daily Sales By City',
                name: 'reports.sales-daily',
            },
            {
                path: route('reports.sale-cash-credit'),
                title: 'Sales Details',
                name: 'reports.sale-cash-credit',
            },
            {
                path: route('reports.purchases_daily'),
                title: 'Daily Purchases',
                name: 'reports.purchases_daily',
            },
            {
                path: route('reports.account-report.receivables'),
                title: 'Accounts Receivables',
                name: 'reports.account-report.receivables',
            },
            {
                path: route('reports.account-report.city-by-receivables'),
                title: 'Accounts Receivables By City',
                name: 'reports.account-report.city-by-receivables',
            },
            {
                path: route('reports.customers.balance'),
                title: 'Customer Balance',
                name: 'reports.customers.balance',
            },
            {
                path: route('reports.customers.last-payment'),
                title: 'Customer Payments',
                name: 'reports.customers.last-payment',
            },
            {
                path: route('reports.account-report.bank-book'),
                title: 'Bank Book',
                name: 'reports.account-report.bank-book',
            },
            {
                path: route('reports.account-report.expenses'),
                title: 'Daily Expenses',
                name: 'reports.account-report.expenses',
            },
            {
                path: route('reports.account-report.index'),
                title: 'Accounts',
                name: 'reports.account-report.index',
            },
            {
                path: route('reports.journal-report.index'),
                title: 'Daily Journal',
                name: 'reports.journal-report.index',
            },
            {
                path: route('reports.sales-daily-average'),
                title: 'Sales Daily Average',
                name: 'reports.sales-daily-average',
            },
        ],
    },
    {
        path: '/settings',
        icon: 'solar:settings-bold-duotone',
        title: 'Settings',
        name: 'settings',
        children: [
            {
                path: route('settings.users.index'),
                title: 'Users',
                name: 'settings.users.index',
            },
            {
                path: route('settings.roles.index'),
                title: 'Roles',
                name: 'settings.roles.index',
            },
            {
                path: route('settings.cities.index'),
                title: 'Cities',
                name: 'settings.cities.index',
            },
            {
                path: route('settings.employees.index'),
                title: 'Employees',
                name: 'settings.employees.index',
            },
        ],
    },
];
export default Menu;
