<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  guide: { type: Object, required: true },
  relatedGuides: { type: Array, default: () => [] },
  seoTitle: { type: String, default: 'Panduan KB Samsat Lamongan' },
});

const logoUrl = '/images/logo-samsat-lamongan.png?v=20261124';
</script>

<template>
  <VApp>
    <Head :title="seoTitle" />

    <div class="guide-page">
      <!-- Top Header -->
      <header class="guide-header">
        <div class="guide-header__inner">
          <Link href="/" class="guide-brand text-decoration-none">
            <img :src="logoUrl" alt="Logo KB Samsat Lamongan" width="42" height="42" class="me-3 flex-shrink-0 object-contain" />
            <div>
              <div class="guide-brand__name">KB Samsat Lamongan</div>
              <div class="guide-brand__sub">Bapenda Provinsi Jawa Timur</div>
            </div>
          </Link>
          <div class="d-flex align-center gap-2">
            <Link href="/panduan" class="guide-nav-btn text-decoration-none">
              <VIcon size="16" class="me-1">mdi-book-open-variant</VIcon>
              Semua Panduan
            </Link>
            <Link href="/wajib-pajak" class="guide-cta-btn text-decoration-none d-none d-sm-inline-flex">
              <VIcon size="16" class="me-1">mdi-chat-processing</VIcon>
              Tanya SALMA AI
            </Link>
          </div>
        </div>
      </header>

      <!-- Main Article -->
      <main>
        <VContainer class="py-8" style="max-width: 960px;">
          <!-- Breadcrumb -->
          <nav aria-label="Breadcrumb" class="guide-breadcrumb mb-5">
            <Link href="/" class="text-decoration-none text-grey-darken-1">Beranda</Link>
            <span class="mx-2 text-grey">/</span>
            <Link href="/panduan" class="text-decoration-none text-grey-darken-1">Panduan Pajak</Link>
            <span class="mx-2 text-grey">/</span>
            <span class="text-grey-darken-4 font-weight-medium">{{ guide.category }}</span>
          </nav>

          <article class="guide-article">
            <header class="mb-6">
              <div class="d-flex flex-wrap align-center gap-2 mb-3">
                <VChip size="small" color="#C0392B" variant="flat">{{ guide.category }}</VChip>
                <VChip size="small" variant="outlined">{{ guide.type }}</VChip>
                <span v-if="guide.updated_at" class="text-caption text-grey-darken-1 ms-auto">
                  Diperbarui: <time :datetime="guide.updated_iso">{{ guide.updated_at }}</time>
                </span>
              </div>

              <h1 class="guide-title">{{ guide.title }}</h1>

              <p v-if="guide.question && guide.question !== guide.title" class="guide-question mt-3">
                <strong>Pertanyaan Terkait:</strong> {{ guide.question }}
              </p>
            </header>

            <VDivider class="mb-6" />

            <!-- Sanitized Semantic Body -->
            <div class="guide-prose" v-html="guide.body_html"></div>

            <!-- Keyword Tags -->
            <div v-if="guide.keywords && guide.keywords.length" class="d-flex flex-wrap gap-2 mt-8 pt-6 border-t">
              <VChip v-for="(kw, i) in guide.keywords" :key="i" size="small" variant="tonal" color="blue-grey">
                #{{ kw }}
              </VChip>
            </div>
          </article>

          <!-- Direct AI Consultation CTA -->
          <div class="guide-ai-cta mt-8">
            <div>
              <h2 class="text-h6 font-weight-bold text-white mb-1">
                Masih Ada Pertanyaan Terkait Kasus Kendaraan Anda?
              </h2>
              <p class="text-body-2 text-white mb-0" style="opacity: 0.88;">
                Konsultasikan langsung dengan SALMA AI — asisten resmi KB Samsat Lamongan aktif 24 jam.
              </p>
            </div>
            <Link href="/wajib-pajak" class="guide-ai-cta__btn text-decoration-none">
              <VIcon size="18" class="me-2">mdi-chat-processing</VIcon>
              Tanya SALMA AI Sekarang
            </Link>
          </div>

          <!-- Related Guides -->
          <section v-if="relatedGuides.length" class="mt-10">
            <h2 class="text-h6 font-weight-bold mb-4" style="color: #1B2838;">
              Panduan Pajak Terkait Lainnya
            </h2>
            <VRow>
              <VCol v-for="rel in relatedGuides" :key="rel.id" cols="12" sm="6">
                <Link :href="rel.url" class="text-decoration-none">
                  <div class="related-card h-100">
                    <div class="text-caption font-weight-bold mb-1" style="color: #C0392B;">{{ rel.category }}</div>
                    <h3 class="text-subtitle-2 font-weight-bold mb-2" style="color: #1E293B;">{{ rel.title }}</h3>
                    <p class="text-caption text-grey-darken-1 mb-0">{{ rel.excerpt }}</p>
                  </div>
                </Link>
              </VCol>
            </VRow>
          </section>
        </VContainer>
      </main>
    </div>
  </VApp>
</template>

<style scoped>
.guide-page { min-height: 100vh; background: #F8FAFC; font-family: 'Poppins', 'Figtree', sans-serif; }
.guide-header { background: #1B2838; color: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.15); }
.guide-header__inner { max-width: 1280px; margin: 0 auto; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.guide-brand { display: flex; align-items: center; color: #fff; }
.guide-brand__name { font-weight: 700; font-size: 1rem; line-height: 1.2; }
.guide-brand__sub { font-size: 0.72rem; opacity: 0.75; }
.guide-nav-btn { display: inline-flex; align-items: center; color: #fff; border: 1px solid rgba(255,255,255,.35); padding: 7px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 500; }
.guide-cta-btn { display: inline-flex; align-items: center; background: #C0392B; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; }

.guide-breadcrumb { font-size: 0.84rem; }
.guide-article { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(15,23,42,.04); }
@media (max-width: 600px) { .guide-article { padding: 20px; } }
.guide-title { font-size: clamp(1.35rem, 2.5vw, 1.95rem); font-weight: 700; color: #0F172A; line-height: 1.3; }
.guide-question { background: #FEF2F2; border-left: 4px solid #C0392B; padding: 12px 16px; border-radius: 0 8px 8px 0; font-size: 0.9rem; color: #334155; }

.guide-prose { color: #1E293B; font-size: 0.96rem; line-height: 1.75; }
.guide-prose :deep(h1),
.guide-prose :deep(h2),
.guide-prose :deep(h3),
.guide-prose :deep(h4) { font-weight: 700; color: #0F172A; margin: 1.35em 0 0.55em; line-height: 1.35; }
.guide-prose :deep(h2) { font-size: 1.25rem; }
.guide-prose :deep(h3) { font-size: 1.08rem; }
.guide-prose :deep(p) { margin-bottom: 1em; }
.guide-prose :deep(ul),
.guide-prose :deep(ol) { margin: 0.75em 0 1.1em 1.35em; }
.guide-prose :deep(li) { margin-bottom: 0.4em; }
.guide-prose :deep(table) { width: 100%; border-collapse: collapse; margin: 1.25em 0; font-size: 0.9rem; }
.guide-prose :deep(th),
.guide-prose :deep(td) { border: 1px solid #CBD5E1; padding: 8px 12px; text-align: left; }
.guide-prose :deep(th) { background: #F1F5F9; font-weight: 600; }

.guide-ai-cta { background: linear-gradient(135deg, #1B2838 0%, #C0392B 100%); border-radius: 16px; padding: 24px 28px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; }
.guide-ai-cta__btn { display: inline-flex; align-items: center; background: #fff; color: #C0392B; padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 0.88rem; white-space: nowrap; }

.related-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; transition: border-color .2s ease, transform .2s ease; }
.related-card:hover { border-color: #C0392B; transform: translateY(-2px); }
</style>
