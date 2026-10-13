import React from 'react';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { Delete } from '@/components/Actions';
import { PageContent, PageHeader } from '@/components/page.jsx';
import NoData from '@/components/NoData.jsx';

const Employees = () => {
    const { employees } = usePage().props;
    const { data, links } = employees;

    return (
        <>
            <Head title={"Employees List"} />
            <PageHeader title={"Employees List"} />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <SearchFilter />
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered"}>
                                <thead>
                                    <tr>
                                        <th width={"40"}>Sr</th>
                                        <th>Name</th>
                                        <th width={"110px"}>Last Modified</th>
                                        <th width={"70"}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(({ id, name, updated_at }, index) => {
                                        return (
                                            <tr key={id}>
                                                <td>{index + 1}</td>
                                                <td>{name}</td>
                                                <td>
                                                    <Moment format={settings.DATE_FORMAT} date={updated_at} />
                                                </td>
                                                <td className={"actions"}>
                                                    <Delete action={"settings.employees.destroy"} id={id} />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            {data.length === 0 && (<NoData label="No employees found." />)}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Employees;
