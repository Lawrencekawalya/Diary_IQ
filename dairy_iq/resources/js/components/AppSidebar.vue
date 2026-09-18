<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Building2, ClipboardList, FileText, FlaskConical, History, LayoutGrid, ShieldCheck, Users } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = page.props.auth.user;

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'New Prediction',
        href: '/milk-batches/create',
        icon: FlaskConical,
    },
    {
        title: 'History',
        href: '/milk-batches',
        icon: History,
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: FileText,
    },
    {
        title: 'Company Settings',
        href: '/company/settings',
        icon: Building2,
    },
    ...(user.role === 'company_admin'
        ? [
            {
                title: 'Company Users',
                href: '/company/users',
                icon: Users,
            },
        ]
        : []),
    ...(user.role === 'super_admin'
        ? [
            {
                title: 'Admin Companies',
                href: '/admin/companies',
                icon: ShieldCheck,
            },
            {
                title: 'Admin Documentation',
                href: '/admin/documentation',
                icon: BookOpen,
            },
        ]
        : []),
].filter((item) => user.role !== 'super_admin' || !['New Prediction', 'History', 'Reports', 'Company Settings'].includes(item.title));

const footerNavItems: NavItem[] = [
    {
        title: 'Approved 11-feature contract',
        href: '/approved-features',
        icon: ClipboardList,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
