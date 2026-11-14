export interface User {
    id: number;
    username: string;
    name: string;
    full_name?: string;
    email?: string;
    department?: string;
    branch?: string;
    is_active: boolean;
    chat_id?: string;
    roles?: string[];
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
        roles: string[];
        permissions: string[];
        isItTeam: boolean;
    };
    flash: {
        message?: string;
        success?: string;
        error?: string;
    };
    ziggy: {
        location: string;
    };
    sidebarOpen: boolean;
};
