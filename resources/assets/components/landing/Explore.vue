<template>
    <div class="landing-explore-component custom-modern-theme">
        <section class="page-wrapper py-5">
            <div class="sr-only">
                <h1>{{ $t('site.exploreTrending') }}</h1>
                <p>{{ $t('explore.subtitle') }}</p>
            </div>

            <div class="container container-compact">
                <div class="main-glass-card shadow-2xl">

                    <div class="nav-menu-modern p-3">
                        <ul class="nav justify-content-center">
                            <li class="nav-item">
                                <router-link to="/" class="nav-link text-white">{{ $t('site.about') }}</router-link>
                            </li>
                            <li v-if="config.show_explore_feed" class="nav-item">
                                <router-link to="/web/explore" class="nav-link text-white font-weight-bold active-link">{{ $t('site.explore') }}</router-link>
                            </li>
                            <li v-if="config.show_directory" class="nav-item">
                                <router-link to="/web/directory" class="nav-link text-white" >{{ $t('site.directory') }}</router-link>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h2 class="display-5 font-weight-bold text-white mb-2">{{ $t("site.exploreTrending") }}</h2>
                            <div class="mx-auto separator-glow"></div>
                            <p class="mt-3 text-muted-modern">{{ $t('site.trendingSubtitle') }}</p>
                        </div>

                        <div v-if="loading" class="loader-container">
                            <div class="spinner-modern"></div>
                            <p class="mt-3 text-primary-glow">{{ $t('site.syncingFeed') }}</p>
                        </div>

                        <div v-else class="feed-grid-wrapper">
                            <div class="feed-list">
                                <post-card v-for="post in feed" :key="post.id" :post="post" :range="ranges[rangeIndex]"
                                    class="modern-post-card" />
                            </div>
                        </div>

                        <div v-if="!loading && feed.length === 0" class="text-center py-5">
                            <i class="fal fa-camera-retro fa-3x text-muted mb-3"></i>
                            <p class="h5 text-white-50">{{ $t('explore.nothingNew') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <footer-component />
        </section>
    </div>
</template>

<script type="text/javascript">
import PostCard from './partials/PostCard';

export default {
    components: {
        "post-card": PostCard
    },

    data() {
        return {
            loading: true,
            config: window.pfl,
            isFetching: false,
            range: 'daily',
            ranges: ['daily', 'monthly', 'yearly'],
            rangeIndex: 0,
            feed: [],
        }
    },

    beforeMount() {
        if (this.config.show_explore_feed == false) {
            this.$router.push('/');
        }
    },

    mounted() {
        this.init();
    },

    methods: {
        init() {
            axios.get('/api/pixelfed/v2/discover/posts/trending?range=daily')
                .then(res => {
                    // Se encontrar mais de 3 posts no diário, carrega imediatamente
                    if (res && res.data.length > 3) {
                        this.feed = res.data;
                        this.loading = false;
                    } else {
                        // Caso contrário, inicia a busca em ranges maiores
                        this.rangeIndex++;
                        this.fetchTrending();
                    }
                })
        },

        fetchTrending() {
            if (this.isFetching || this.rangeIndex >= 3) {
                return;
            }
            this.isFetching = true;

            axios.get('/api/pixelfed/v2/discover/posts/trending', {
                params: {
                    range: this.ranges[this.rangeIndex]
                }
            })
            .then(res => {
                if (res && res.data.length > 3) {
                    // Encontrou resultados suficientes no range atual
                    this.feed = res.data;
                    this.loading = false;
                } else {
                    // Tenta o próximo range (mensal -> anual)
                    this.rangeIndex++;
                    this.isFetching = false;
                    this.fetchTrending();
                }
            })
            .catch(() => {
                this.isFetching = false;
            });
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

.active-link {
    color: #10c5f8 !important;
    border-bottom: 2px solid #10c5f8;
    text-shadow: 0 0 10px rgba(16, 197, 248, 0.5);
}

/* Elementos Visuais */
.separator-glow {
    width: 60px;
    height: 4px;
    background: #10c5f8;
    border-radius: 2px;
    box-shadow: 0 0 15px rgba(16, 197, 248, 0.6);
}

.text-muted-modern {
    color: #718096;
    font-size: 0.95rem;
}

/* Grid de Feed Moderno */
.feed-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 1.5rem;
}

/* Loader Moderno */
.loader-container {
    min-height: 400px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.spinner-modern {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(16, 197, 248, 0.1);
    border-top: 3px solid #10c5f8;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.text-primary-glow {
    color: #10c5f8;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-size: 0.8rem;
}

.modern-post-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 15px;
    overflow: hidden;
}

.modern-post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 576px) {
    .feed-list {
        grid-template-columns: 1fr;
    }

    .main-glass-card {
        border-radius: 12px;
    }
}
</style>
