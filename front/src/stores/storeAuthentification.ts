import {reactive} from 'vue';
import {apiStore} from '@/util/apiStore';
import type {LoginResult, User} from '@/types';

export const storeAuthentification = reactive({
    estConnecte: false,
    utilisateurConnecte: null as User | null,

    async login(login: string, password: string): Promise<LoginResult> {
        try {
            this.utilisateurConnecte = await apiStore.login(login, password);
            this.estConnecte = true;
            return { success: true };
        } catch (err: any) {
            this.utilisateurConnecte = null;
            this.estConnecte = false;
            return { success: false, error: err.message };
        }
    },

    async logout(): Promise<LoginResult> {
        try {
            await apiStore.logout();
            this.utilisateurConnecte = null;
            this.estConnecte = false;
            return { success: true };
        } catch (err: any) {
            return { success: false, error: err.message };
        }
    },

    async register(login: string, email: string, password: string): Promise<LoginResult> {
        try {
            await apiStore.register({ login, email, password });
            return { success: true };
        } catch (err: any) {
            return { success: false, error: err.message };
        }
    },

    async refresh(): Promise<LoginResult> {
        try {
            await apiStore.refresh();
            return { success: true };
        } catch {
            this.utilisateurConnecte = null;
            this.estConnecte = false;
            return { success: false, error: 'Session expirée' };
        }
    }
});
