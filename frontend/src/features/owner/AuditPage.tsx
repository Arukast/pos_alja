import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageHeader } from '../../components/PageHeader';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Pagination } from '../../components/ui/Pagination';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';

export function AuditPage() {
    const [search, setSearch] = useState('');
    const [sort, setSort] = useState('created_at');
    const [direction, setDirection] = useState('desc');
    const [page, setPage] = useState(1);

    const query = useQuery({
        queryKey: ['audit-logs', search, sort, direction, page],
        queryFn: () =>
            api.auditLogs({
                search,
                sort,
                direction,
                page,
            }),
    });

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Owner insights"
                title="Audit Trail"
                description="Searchable record of important system changes."
                actions={
                    <>
                        <div className="w-56">
                            <Input
                                aria-label="Search audit logs"
                                placeholder="Search user, action, description"
                                value={search}
                                onChange={(event) => {
                                    setSearch(event.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        <Select
                            aria-label="Sort field"
                            value={sort}
                            onChange={(event) => setSort(event.target.value)}
                        >
                            <option value="created_at">Newest</option>
                            <option value="username">Username</option>
                            <option value="role">Role</option>
                            <option value="action">Action</option>
                        </Select>
                        <Select
                            aria-label="Sort direction"
                            value={direction}
                            onChange={(event) => setDirection(event.target.value)}
                        >
                            <option value="desc">Descending</option>
                            <option value="asc">Ascending</option>
                        </Select>
                    </>
                }
            />

            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : null}

                    {query.data ? (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Time</TableHeaderCell>
                                        <TableHeaderCell>User</TableHeaderCell>
                                        <TableHeaderCell>Role</TableHeaderCell>
                                        <TableHeaderCell>Action</TableHeaderCell>
                                        <TableHeaderCell>Description</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.data.length === 0 ? (
                                        <TableEmpty colSpan={5}>No audit entries match.</TableEmpty>
                                    ) : (
                                        query.data.data.map((log) => (
                                            <TableRow key={log.id}>
                                                <TableCell>{formatDate(log.created_at)}</TableCell>
                                                <TableCell>{log.username}</TableCell>
                                                <TableCell className="capitalize">{log.role}</TableCell>
                                                <TableCell>{log.action}</TableCell>
                                                <TableCell>{log.description}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <Pagination
                                page={query.data.meta.current_page}
                                lastPage={query.data.meta.last_page}
                                onPageChange={setPage}
                            />
                        </>
                    ) : null}
                </CardBody>
            </Card>
        </div>
    );
}
