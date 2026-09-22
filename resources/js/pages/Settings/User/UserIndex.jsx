import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { InertiaEdit, ToggleAction } from '@/components/Actions';
import NoData from '@/components/NoData.jsx';

const Users = () => {
    const { users } = usePage().props;
    const { data, links } = users;

    return (
        <>
            <Head title="User List" />
            <PageHeader title="User List" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={"Users List"} buttons={(
                        <>
                            <InertiaLink href={route("settings.users.create")} className="btn btn-xs  btn-primary">
                                <Icon icon={"solar:add-bold-duotone"} /> Create User
                            </InertiaLink>
                        </>
                    )} />
                    <PanelBody>
                        <SearchFilter />
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered"}>
                                <thead>
                                <tr>
                                    <th width={"40"}>Sr</th>
                                    <th>User Name</th>
                                    <th>User Email</th>
                                    <th className={"w-1"}>Role</th>
                                    <th className={"w-1"}>Last Modified</th>
                                    <th className={"w-1"}>Active</th>
                                    <th className={"w-1"}>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {data.map(({ id, name, email, role, active, updated_at }, index) => {
                                    return (
                                        <tr key={id}>
                                            <td>{index + 1}</td>
                                            <td>{name}</td>
                                            <td>{email}</td>
                                            <td className={"w-1"}>{role.name}</td>

                                            <td className={"w-1"}>
                                                <Moment
                                                    format={settings.DATE_FORMAT}
                                                    date={updated_at} />
                                            </td>
                                            <td className={"w-1 text-center"}>
                                                {!active &&
                                                    (<Icon className={"text-red-500"} icon={"solar:close-circle-bold-duotone"} />)}
                                                {active &&
                                                    (<Icon className={"text-green-500"} icon={"solar:check-circle-bold-duotone"} />)}

                                            </td>
                                            <td className={"actions"}>
                                                <InertiaEdit href={route("settings.users.edit", id)} />
                                                <ToggleAction action={"settings.users.destroy"}
                                                              message={"Active/Deactivate the user."} id={id} />
                                            </td>
                                        </tr>
                                    );
                                })}
                                </tbody>
                            </table>
                            {data.length === 0 && (<NoData label="No users found." />)}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Users;
