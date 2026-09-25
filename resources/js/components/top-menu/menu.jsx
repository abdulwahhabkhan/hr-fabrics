import { dashboard } from '@/routes';
import brands from '@/routes/catalog/brands';
import finish from '@/routes/catalog/finish';
import products from '@/routes/catalog/products';
import customers from '@/routes/sales/customers';
import orders from '@/routes/sales/orders';
import returns from '@/routes/sales/returns';
import widgets from '@/routes/widgets';
import suppliers from '@/routes/purchases/suppliers';
import fabricReceivings from '@/routes/purchases/fabric-receivings';
import pos from '@/routes/purchases/pos';
import por from '@/routes/purchases/por';
import inventories from '@/routes/stocks/inventories';
import conversions from '@/routes/stocks/conversions';
import valueAddition from '@/routes/stocks/value-addition';
import stocks from '@/routes/stocks';
import storeTransfers from '@/routes/stocks/store-transfers';
import accountsModule from '@/routes/accounts/accounts';
import journals from '@/routes/accounts/journals';
import ledgers from '@/routes/accounts/ledgers';
import cashBank from '@/routes/accounts/cash-bank';
import accounts from '@/routes/accounts';
import reports from '@/routes/reports';
import po from '@/routes/reports/po';
import fastSelling from '@/routes/reports/fast-selling';
import purchasedDate from '@/routes/reports/purchased-date';
import inMethod from '@/routes/reports/in';
import reportCustomers from '@/routes/reports/customers';
import accountReport from '@/routes/reports/account-report';
import journalReport from '@/routes/reports/journal-report';
import users from '@/routes/settings/users';
import roles from '@/routes/settings/roles';
import cities from '@/routes/settings/cities';
import employees from '@/routes/settings/employees';

const Menu = [
    {
        path: dashboard().url,
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
                path: brands.index().url,
                title: 'Brands',
                name: 'catalog.brands.index',
            },
            {
                path: finish.index().url,
                title: 'Finish',
                name: 'catalog.finish.index',
            },
            {
                path: products.index().url,
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
                path: customers.index().url,
                title: 'Manage Customers',
                name: 'sales.customers.index',
            },
            {
                path: orders.index().url,
                title: 'Sales Invoices',
                name: 'sales.orders.index',
            },
            {
                path: returns.index().url,
                title: 'Sales Returns',
                name: 'sales.returns.index',
            },
            {
                path: widgets.averageSaleMeter().url,
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
                path: suppliers.index().url,
                title: 'Suppliers',
                name: 'purchases.suppliers.index',
            },
            {
                path: fabricReceivings.index().url,
                title: 'Fabric Receiving',
                name: 'purchases.fabric-receivings.index',
            },
            {
                path: pos.index().url,
                title: 'Fabric Purchases',
                name: 'purchases.pos.index',
            },
            {
                path: por.index().url,
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
                path: inventories.index().url,
                title: 'Inventory',
                name: 'stocks.inventories.index',
            },
            {
                path: conversions.index().url,
                title: 'Conversion',
                name: 'stocks.conversions.index',
            },
            {
                path: valueAddition.index().url,
                title: 'Value Addition',
                name: 'stocks.value-addition.index',
            },
            {
                path: stocks.valueByBrand().url,
                title: 'Value By Brand',
                name: 'stocks.value-by-brand',
            },
            {
                path: stocks.productHistory().url,
                title: 'Product History',
                name: 'stocks.product-history',
            },
            {
                path: storeTransfers.index().url,
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
                path: accountsModule.index().url,
                title: 'Accounts',
                name: 'accounts.accounts.index',
            },
            {
                path: journals.index().url,
                title: 'Journals',
                name: 'accounts.journals.index',
            },
            {
                path: ledgers.index().url,
                title: 'Ledgers',
                name: 'accounts.ledgers.index',
            },
            {
                path: cashBank.summary().url,
                title: 'Cash Bank Summary',
                name: 'accounts.cash-bank.summary',
            },
            {
                path: accounts.incomeStatement().url,
                title: 'Income Statement',
                name: 'accounts.income-statement',
            },
            {
                path: accounts.balanceHistory().url,
                title: 'Balance History',
                name: 'accounts.balance-history',
            },
            {
                path: accounts.saleSummary().url,
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
            {
                path: po.purchases().url,
                title: 'Purchases',
                name: 'reports.po.purchases',
            },
            {
                path: fastSelling.products().url,
                title: 'Fast Selling Products',
                name: 'reports.fast-selling.products',
            },
            {
                path: purchasedDate.products().url,
                title: 'Products by Purchase Date',
                name: 'reports.purchased-date.products',
            },
            {
                path: reports.summary().url,
                title: 'Daily Summary',
                name: 'reports.summary',
            },
            {
                path: inMethod.out.transactions.summary().url,
                title: 'In/Out Transaction Summary',
                name: 'reports.in.out.transactions.summary',
            },
            {
                path: reports.salesDaily().url,
                title: 'Daily Sales By City',
                name: 'reports.sales-daily',
            },
            {
                path: reports.saleCashCredit().url,
                title: 'Sales Details',
                name: 'reports.sale-cash-credit',
            },
            {
                path: reports.purchases_daily().url,
                title: 'Daily Purchases',
                name: 'reports.purchases_daily',
            },
            {
                path: accountReport.receivables().url,
                title: 'Accounts Receivables',
                name: 'reports.account-report.receivables',
            },
            {
                path: accountReport.cityByReceivables().url,
                title: 'Accounts Receivables By City',
                name: 'reports.account-report.city-by-receivables',
            },
            {
                path: reportCustomers.balance().url,
                title: 'Customer Balance',
                name: 'reports.customers.balance',
            },
            {
                path: reportCustomers.lastPayment().url,
                title: 'Customer Payments',
                name: 'reports.customers.last-payment',
            },
            {
                path: accountReport.bankBook().url,
                title: 'Bank Book',
                name: 'reports.account-report.bank-book',
            },
            {
                path: accountReport.expenses().url,
                title: 'Daily Expenses',
                name: 'reports.account-report.expenses',
            },
            {
                path: accountReport.index().url,
                title: 'Accounts',
                name: 'reports.account-report.index',
            },
            {
                path: journalReport.index().url,
                title: 'Daily Journal',
                name: 'reports.journal-report.index',
            },
            {
                path: reports.salesDailyAverage().url,
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
                path: users.index().url,
                title: 'Users',
                name: 'settings.users.index',
            },
            {
                path: roles.index().url,
                title: 'Roles',
                name: 'settings.roles.index',
            },
            {
                path: cities.index().url,
                title: 'Cities',
                name: 'settings.cities.index',
            },
            {
                path: employees.index().url,
                title: 'Employees',
                name: 'settings.employees.index',
            },
        ],
    },
];

export default Menu;
