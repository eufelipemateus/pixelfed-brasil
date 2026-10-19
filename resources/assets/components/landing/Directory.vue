<template>
    <div class="landing-directory-component custom-modern-theme">
        <section class="page-wrapper py-5">
            <div class="container container-compact">
                <div class="main-glass-card shadow-2xl">

                    <div class="nav-menu-modern p-3">
                        <ul class="nav justify-content-center">
                            <li class="nav-item">
                                <router-link to="/" class="nav-link text-white">{{ $t('site.about') }}</router-link>
                            </li>
                            <li v-if="config.show_explore_feed" class="nav-item">
                                <router-link to="/web/explore" class="nav-link text-white">{{ $t('site.explore') }}</router-link>
                            </li>
                            <li v-if="config.show_directory" class="nav-item">
                                <router-link to="/web/directory" class="nav-link text-white font-weight-bold active-link">{{ $t('site.directory') }}</router-link>
                            </li>

                        </ul>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h2 class="display-5 font-weight-bold text-white mb-2">{{ $t("site.discoverAccounts") }}</h2>
                            <div class="mx-auto separator-glow"></div>
                            <p class="mt-3 text-muted-modern" style="font-size:0.95rem; color:#718096;">{{ $t('site.intro') }}</p>
                        </div>

                        <div v-if="loading" class="loader-container">
                            <div class="spinner-modern"></div>
                            <p class="mt-3 text-primary-glow">{{ $t('site.locatingCreators') }}</p>
                        </div>

                        <div v-else class="directory-grid-wrapper">
                            <div class="feed-list">
                                <user-card v-for="account in feed" :key="account.id" :account="account"
                                    class="modern-user-card" />
                            </div>

                            <intersect v-if="canLoadMore && !isEmpty" @enter="enterIntersect">
                                <div class="d-flex justify-content-center pt-5 pb-3">
                                    <div v-if="isLoadingMore" class="spinner-modern-sm"></div>
                                </div>
                            </intersect>
                        </div>

                        <div v-if="isEmpty" class="empty-state-container py-5">
                            <div class="glass-inner-card p-5 text-center">
                                <i class="fal fa-users-slash fa-4x text-bluegray-500 mb-3"></i>
                                <p class="lead font-weight-bold text-white mb-0">{{ $t('site.noProfileFound') }}</p>
                                <p class="text-muted">{{ $t('site.beFirstToPopulate') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer-component />
        </section>
    </div>
</template>

<script type="text/javascript">
import UserCard from './partials/UserCard';
import Intersect from 'vue-intersect';

export default {
    components: {
        "user-card": UserCard,
        "intersect": Intersect,
    },

    data() {
        return {
            loading: true,
            config: window.pfl,
            pagination: undefined,
            feed: [],
            isEmpty: false,
            canLoadMore: false,
            isIntersecting: false,
            isLoadingMore: false
        }
    },

    beforeMount() {
        if (this.config.show_directory == false) {
            this.$router.push('/');
        }
    },

    mounted() {
        this.init();
    },

    methods: {
        init() {
            axios.get('/api/landing/v1/directory')
                .then(res => {
                    if (!res.data.data.length) {
                        this.isEmpty = true;
                    }
                    this.feed = res.data.data;
                    this.pagination = { ...res.data.links, ...res.data.meta };
                })
                .finally(() => {
                    this.canLoadMore = true;
                    this.$nextTick(() => {
                        this.loading = false;
                    })
                })
        },

        enterIntersect(e) {
            if (this.isIntersecting || !this.pagination.next_cursor) {
                return;
            }
            this.isIntersecting = true;
            this.isLoadingMore = true;

            axios.get('/api/landing/v1/directory', {
                params: {
                    cursor: this.pagination.next_cursor
                }
            })
                .then(res => {
                    this.feed.push(...res.data.data);
                    this.pagination = { ...res.data.links, ...res.data.meta };
                })
                .finally(() => {
                    if (this.pagination.next_cursor) {
                        this.canLoadMore = true;
                    } else {
                        this.canLoadMore = false;
                    }
                    this.isLoadingMore = false;
                    this.isIntersecting = false;
                });
            console.log(e);
        }
    }
}
</script>
<style scoped>
/* Base do Tema */
.custom-modern-theme {
    background: radial-gradient(circle at top right, #1a202c, #0d1117);
    min-height: 100vh;
    color: #e2e8f0;
}

/* Glassmorphism Card */
.main-glass-card {
    background: rgba(23, 27, 34, 0.8);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    overflow: hidden;
}

/* Navegação */
.nav-menu-modern {
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
/* Grid específico para Cards de Usuário */
.feed-list {
    display: grid;
    /* Min-max de 250px para caber mais perfis por linha que os posts */
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 1.5rem;
}

/* Ajuste para o componente UserCard se comportar bem no grid */
.modern-user-card {
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    border-radius: 20px !important;
    transition: all 0.3s ease;
}

.modern-user-card:hover {
    background: rgba(16, 197, 248, 0.05) !important;
    border-color: rgba(16, 197, 248, 0.3) !important;
    transform: translateY(-5px);
}

/* Inner card para estados vazios */
.glass-inner-card {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

/* Spinner menor para o infinite scroll */
.spinner-modern-sm {
    width: 30px;
    height: 30px;
    border: 2px solid rgba(16, 197, 248, 0.1);
    border-top: 2px solid #10c5f8;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Ajuste da Nav Link ativa */
.active-link {
    color: #10c5f8 !important;
    border-bottom: 2px solid #10c5f8;
    text-shadow: 0 0 10px rgba(16, 197, 248, 0.5);
}

.separator-glow {
    width: 60px;
    height: 4px;
    background: #10c5f8;
    border-radius: 2px;
    box-shadow: 0 0 15px rgba(16, 197, 248, 0.6);
}
@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

@media (max-width: 576px) {
    .feed-list {
        grid-template-columns: 1fr;
        /* 1 coluna no celular */
    }
}
</style>
