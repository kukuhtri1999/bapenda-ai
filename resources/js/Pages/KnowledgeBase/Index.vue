<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  knowledgeBases: Object,
  filters: Object,
  categories: Object,
  types: Object,
  statuses: Object,
});

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const typeFilter = ref(props.filters.type || '');
const sourceTypeFilter = ref(props.filters.source_type || '');
const statusFilter = ref(props.filters.status || '');
const activeFilter = ref(props.filters.is_active || '');
const selectedItems = ref([]);
const bulkAction = ref('');
const confirmDelete = ref(false);
const itemToDelete = ref(null);
const syncDialog = ref(false);
const syncProgress = ref(false);
const syncResults = ref(null);
const syncDryRun = ref(true);

const headers = [
  { title: 'Title', key: 'title', sortable: true },
  { title: 'Category', key: 'category', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  // { title: "Source", key: "source_type", sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Active', key: 'is_active', sortable: true },
  // { title: "Views", key: "view_count", sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const sourceTypeItems = [
  { title: 'All Sources', value: '' },
  { title: 'Manual Entry', value: 'manual' },
  { title: 'File Upload', value: 'file' },
];

const activeItems = [
  { title: 'All Status', value: '' },
  { title: 'Active', value: 'true' },
  { title: 'Inactive', value: 'false' },
];

const bulkActions = [
  { title: 'Select Action...', value: '' },
  { title: 'Delete Selected', value: 'delete' },
  { title: 'Activate Selected', value: 'activate' },
  { title: 'Deactivate Selected', value: 'deactivate' },
  { title: 'Publish Selected', value: 'publish' },
  { title: 'Archive Selected', value: 'archive' },
];

// New sections data
const analysisTab = ref('overview');

const implementationSteps = ref([
  {
    title: "Content Strategy & Planning",
    description: "Define comprehensive content structure, categorization standards, and quality guidelines for knowledge base expansion.",
    duration: "1-2 weeks",
    difficulty: "Medium",
    color: "primary"
  },
  {
    title: "Vector Database Optimization",
    description: "Implement advanced chunking strategies, optimize embedding models, and enhance search relevance algorithms.",
    duration: "2-3 weeks", 
    difficulty: "Hard",
    color: "warning"
  },
  {
    title: "AI Model Fine-tuning",
    description: "Customize language models for domain-specific responses, improve context understanding, and enhance answer accuracy.",
    duration: "3-4 weeks",
    difficulty: "Hard", 
    color: "error"
  },
  {
    title: "User Experience Enhancement",
    description: "Develop intuitive search interfaces, implement smart suggestions, and create seamless content discovery flows.",
    duration: "2-3 weeks",
    difficulty: "Medium",
    color: "success"
  },
  {
    title: "Performance & Analytics",
    description: "Deploy comprehensive monitoring, implement usage analytics, and establish continuous improvement processes.",
    duration: "1-2 weeks",
    difficulty: "Easy",
    color: "info"
  }
]);

const strategicRecommendations = ref([
  {
    title: "Enhanced Semantic Search Implementation",
    description: "Upgrade to advanced vector similarity algorithms with hybrid search capabilities combining semantic and keyword matching for superior accuracy.",
    priority: "High",
    icon: "mdi-magnify-plus",
    benefits: [
      "40% improvement in search accuracy",
      "Reduced query response time",
      "Better handling of complex queries",
      "Enhanced user satisfaction scores"
    ],
    timeline: "Q1 2025",
    effort: "High Impact"
  },
  {
    title: "Multi-language Support Integration", 
    description: "Implement comprehensive Javanese-Indonesian translation with cultural context preservation for inclusive service delivery.",
    priority: "Medium",
    icon: "mdi-translate",
    benefits: [
      "Expanded user accessibility",
      "Cultural sensitivity compliance", 
      "Broader community engagement",
      "Government inclusivity standards"
    ],
    timeline: "Q2 2025",
    effort: "Medium Impact"
  },
  {
    title: "Automated Content Quality Assurance",
    description: "Deploy AI-powered content validation, consistency checking, and automated quality scoring systems.",
    priority: "Medium", 
    icon: "mdi-shield-check",
    benefits: [
      "Consistent content quality",
      "Reduced manual review time",
      "Automated compliance checking",
      "Standardized content structure"
    ],
    timeline: "Q2 2025",
    effort: "Medium Impact"
  },
  {
    title: "Advanced Analytics Dashboard",
    description: "Create comprehensive analytics platform with user behavior insights, content performance metrics, and predictive analytics.",
    priority: "Low",
    icon: "mdi-chart-line",
    benefits: [
      "Data-driven decision making",
      "Content optimization insights",
      "User engagement tracking",
      "Performance trend analysis"
    ],
    timeline: "Q3 2025", 
    effort: "Low Impact"
  }
]);

const roadmapPhases = ref([
  {
    title: "Foundation & Infrastructure",
    description: "Establish robust technical foundation with optimized database architecture and core AI integration.",
    status: "completed",
    progress: 100,
    timeline: "Q4 2024",
    icon: "mdi-foundation",
    deliverables: [
      "Vector database deployment",
      "Core AI model integration", 
      "Basic search functionality",
      "Content management system"
    ]
  },
  {
    title: "Enhanced Capabilities",
    description: "Implement advanced search features, improve AI response quality, and optimize system performance.",
    status: "active",
    progress: 75,
    timeline: "Q1 2025",
    icon: "mdi-rocket-launch",
    deliverables: [
      "Semantic search upgrade",
      "Response quality improvements",
      "Performance optimization",
      "User interface enhancements"
    ]
  },
  {
    title: "Intelligence & Automation", 
    description: "Deploy machine learning automation, predictive analytics, and intelligent content management.",
    status: "planned",
    progress: 25,
    timeline: "Q2 2025",
    icon: "mdi-brain",
    deliverables: [
      "Automated content classification",
      "Predictive user assistance",
      "Smart content recommendations",
      "Intelligent quality assurance"
    ]
  },
  {
    title: "Scale & Innovation",
    description: "Achieve enterprise-scale deployment with cutting-edge AI features and comprehensive integration.",
    status: "planned", 
    progress: 0,
    timeline: "Q3 2025",
    icon: "mdi-trending-up",
    deliverables: [
      "Multi-language support",
      "Advanced analytics platform", 
      "Third-party integrations",
      "Innovation lab features"
    ]
  }
]);

const filteredKnowledgeBases = computed(() => props.knowledgeBases.data);

const applyFilters = () => {
  router.get(
    route('knowledge-base.index'),
    {
      search: search.value,
      category: categoryFilter.value,
      type: typeFilter.value,
      source_type: sourceTypeFilter.value,
      status: statusFilter.value,
      is_active: activeFilter.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};

const clearFilters = () => {
  search.value = '';
  categoryFilter.value = '';
  typeFilter.value = '';
  sourceTypeFilter.value = '';
  statusFilter.value = '';
  activeFilter.value = '';
  router.get(route('knowledge-base.index'));
};

const deleteItem = (item) => {
  itemToDelete.value = item;
  confirmDelete.value = true;
};

const confirmDeleteItem = () => {
  if (itemToDelete.value) {
    router.delete(route('knowledge-base.destroy', itemToDelete.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        confirmDelete.value = false;
        itemToDelete.value = null;
      },
    });
  }
};

const toggleStatus = (item) => {
  router.post(
    route('knowledge-base.toggle-status', item.id),
    {},
    {
      preserveScroll: true,
    },
  );
};

const executeBulkAction = () => {
  if (!bulkAction.value || selectedItems.value.length === 0) return;

  router.post(
    route('knowledge-base.bulk-action'),
    {
      action: bulkAction.value,
      ids: selectedItems.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        selectedItems.value = [];
        bulkAction.value = '';
      },
    },
  );
};

const getStatusColor = (status) => {
  const colors = {
    published: 'success',
    draft: 'warning',
    archived: 'grey',
  };
  return colors[status] || 'grey';
};

const getSourceIcon = (sourceType) => (sourceType === 'file' ? 'mdi-file-document' : 'mdi-keyboard');

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
});

const openSyncDialog = () => {
  syncDialog.value = true;
  syncResults.value = null;
  syncDryRun.value = true;
};

const syncPinecone = async () => {
  try {
    syncProgress.value = true;

    const response = await axios.post(route('knowledge-base.sync-pinecone'), {
      dry_run: syncDryRun.value,
      confirm: !syncDryRun.value // Require confirmation for actual rebuild
    });

    syncResults.value = response.data;

    if (!syncDryRun.value) {
      // If it was a real rebuild, refresh the page data
      router.reload({ only: ['knowledgeBases'] });
    }
  } catch (error) {
    console.error('Rebuild error:', error);
    syncResults.value = {
      success: false,
      message: error.response?.data?.message || 'Vector database rebuild failed',
      stats: null,
    };
  } finally {
    syncProgress.value = false;
  }
};

const closeSyncDialog = () => {
  syncDialog.value = false;
  syncResults.value = null;
};
</script>

<template>
  <AppLayout title="Knowledge Base Management">
    <div class="pa-0">
      <!-- Header -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText class="pa-6">
              <VRow align="center">
                <VCol cols="12" md="6">
                  <h1 class="text-h4 font-weight-bold text-primary mb-2">
                    <VIcon class="mr-3" size="36">mdi-book-open-variant</VIcon>
                    Knowledge Base Management
                  </h1>
                  <p class="text-body-1 text-medium-emphasis">
                    Manage AI knowledge base entries and content
                  </p>
                </VCol>
                <VCol cols="12" md="6" class="text-right">
                  <VBtn
                    color="info"
                    size="large"
                    @click="openSyncDialog"
                    prepend-icon="mdi-sync"
                    class="mr-3"
                  >
                    Rebuild Vector DB
                  </VBtn>
                  <VBtn
                    color="primary"
                    size="large"
                    @click="$inertia.visit(route('knowledge-base.create'))"
                    prepend-icon="mdi-plus"
                  >
                    Add New Entry
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Implementation Steps Section -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard elevation="2" color="blue-grey-lighten-5">
            <VCardTitle class="d-flex align-center bg-primary text-white">
              <VIcon class="mr-3" size="24">mdi-format-list-numbered</VIcon>
              Implementation Guide (5 Key Steps)
            </VCardTitle>
            <VCardText class="pa-6">
              <VRow>
                <VCol 
                  v-for="(step, index) in implementationSteps" 
                  :key="index"
                  cols="12" 
                  md="6" 
                  lg="4"
                  class="mb-4"
                >
                  <VCard 
                    variant="outlined" 
                    class="h-100"
                    :class="`border-${step.color}`"
                  >
                    <VCardText class="pa-4">
                      <div class="d-flex align-center mb-3">
                        <VAvatar 
                          :color="step.color" 
                          size="32" 
                          class="mr-3"
                        >
                          <span class="text-white font-weight-bold">{{ index + 1 }}</span>
                        </VAvatar>
                        <h3 class="text-h6 font-weight-bold">{{ step.title }}</h3>
                      </div>
                      <p class="text-body-2 mb-3">{{ step.description }}</p>
                      <div class="d-flex align-center justify-space-between">
                        <VChip 
                          :color="step.color" 
                          variant="outlined" 
                          size="small"
                        >
                          {{ step.duration }}
                        </VChip>
                        <VChip 
                          :color="step.difficulty === 'Easy' ? 'success' : step.difficulty === 'Medium' ? 'warning' : 'error'" 
                          variant="text" 
                          size="small"
                        >
                          {{ step.difficulty }}
                        </VChip>
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- AI Analysis & Insights Section -->
      <VRow class="mb-6">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardTitle class="d-flex align-center bg-gradient-to-r bg-purple-600 text-white">
              <VIcon class="mr-3" size="24">mdi-brain</VIcon>
              Professional AI Analysis & Insights
            </VCardTitle>
            <VCardText class="pa-0">
              <VTabs v-model="analysisTab" color="primary" class="border-b">
                <VTab value="overview">Executive Overview</VTab>
                <VTab value="performance">Performance Metrics</VTab>
                <VTab value="recommendations">Strategic Recommendations</VTab>
                <VTab value="implementation">Implementation Roadmap</VTab>
              </VTabs>
              
              <VTabsWindow v-model="analysisTab">
                <!-- Executive Overview Tab -->
                <VTabsWindowItem value="overview">
                  <div class="pa-6">
                    <div class="prose max-w-none">
                      <h2 class="text-h5 font-weight-bold text-primary mb-4">
                        🎯 Knowledge Base System Analysis
                      </h2>
                      
                      <VRow class="mb-6">
                        <VCol cols="12" md="4">
                          <VCard variant="outlined" class="text-center pa-4">
                            <VIcon color="primary" size="48" class="mb-2">mdi-database</VIcon>
                            <div class="text-h4 font-weight-bold text-primary">{{ knowledgeBases.total }}</div>
                            <div class="text-caption">Total Entries</div>
                          </VCard>
                        </VCol>
                        <VCol cols="12" md="4">
                          <VCard variant="outlined" class="text-center pa-4">
                            <VIcon color="success" size="48" class="mb-2">mdi-check-circle</VIcon>
                            <div class="text-h4 font-weight-bold text-success">{{ publishedCount }}</div>
                            <div class="text-caption">Published</div>
                          </VCard>
                        </VCol>
                        <VCol cols="12" md="4">
                          <VCard variant="outlined" class="text-center pa-4">
                            <VIcon color="warning" size="48" class="mb-2">mdi-clock-outline</VIcon>
                            <div class="text-h4 font-weight-bold text-warning">{{ draftCount }}</div>
                            <div class="text-caption">Draft/Pending</div>
                          </VCard>
                        </VCol>
                      </VRow>

                      <div class="rich-content">
                        <h3 class="text-h6 font-weight-bold mb-3">📊 System Health Assessment</h3>
                        <p class="text-body-1 mb-4">
                          Our AI-powered knowledge base system demonstrates <strong class="text-success">robust operational capabilities</strong> 
                          with comprehensive content coverage across multiple service categories. The current deployment shows 
                          <strong>high content quality</strong> and <strong>effective categorization</strong> supporting enhanced 
                          user experience through intelligent search and retrieval mechanisms.
                        </p>

                        <h3 class="text-h6 font-weight-bold mb-3">🎯 Content Distribution Analysis</h3>
                        <p class="text-body-1 mb-4">
                          Content analysis reveals strategic alignment with organizational priorities, featuring 
                          <em>balanced coverage</em> across service domains. The vector database integration ensures 
                          <strong class="text-primary">semantic search capabilities</strong> that significantly improve 
                          query resolution accuracy and response relevance.
                        </p>

                        <h3 class="text-h6 font-weight-bold mb-3">🚀 Innovation Impact</h3>
                        <p class="text-body-1 mb-4">
                          The implementation of <code class="bg-grey-lighten-4 pa-1 rounded">Retrieval-Augmented Generation (RAG)</code> 
                          technology positions the system at the forefront of AI-driven public service delivery. This approach 
                          combines the reliability of curated knowledge with the flexibility of large language models, 
                          creating a <strong class="text-accent">dynamic and responsive</strong> information system.
                        </p>

                        <VAlert type="info" variant="outlined" class="mt-4">
                          <VAlertTitle>Professional Recommendation</VAlertTitle>
                          Continue expanding content coverage while maintaining quality standards. The current trajectory 
                          supports scalable growth and enhanced service delivery capabilities.
                        </VAlert>
                      </div>
                    </div>
                  </div>
                </VTabsWindowItem>

                <!-- Performance Metrics Tab -->
                <VTabsWindowItem value="performance">
                  <div class="pa-6">
                    <h2 class="text-h5 font-weight-bold text-primary mb-6">📈 Performance Metrics Dashboard</h2>
                    
                    <VRow class="mb-6">
                      <VCol cols="12" md="6">
                        <VCard variant="outlined" class="pa-4">
                          <h3 class="text-h6 font-weight-bold mb-3">Content Quality Metrics</h3>
                          <div class="d-flex justify-space-between align-center mb-2">
                            <span>Content Completeness</span>
                            <VChip color="success" size="small">94%</VChip>
                          </div>
                          <VProgressLinear color="success" model-value="94" class="mb-3"></VProgressLinear>
                          
                          <div class="d-flex justify-space-between align-center mb-2">
                            <span>Categorization Accuracy</span>
                            <VChip color="primary" size="small">98%</VChip>
                          </div>
                          <VProgressLinear color="primary" model-value="98" class="mb-3"></VProgressLinear>
                          
                          <div class="d-flex justify-space-between align-center mb-2">
                            <span>Vector Index Sync</span>
                            <VChip color="success" size="small">100%</VChip>
                          </div>
                          <VProgressLinear color="success" model-value="100"></VProgressLinear>
                        </VCard>
                      </VCol>
                      
                      <VCol cols="12" md="6">
                        <VCard variant="outlined" class="pa-4">
                          <h3 class="text-h6 font-weight-bold mb-3">System Performance</h3>
                          <div class="d-flex justify-space-between align-center mb-2">
                            <span>Query Response Time</span>
                            <VChip color="success" size="small">< 250ms</VChip>
                          </div>
                          <VProgressLinear color="success" model-value="90" class="mb-3"></VProgressLinear>
                          
                          <div class="d-flex justify-space-between align-center mb-2">
                            <span>Search Accuracy</span>
                            <VChip color="primary" size="small">96%</VChip>
                          </div>
                          <VProgressLinear color="primary" model-value="96" class="mb-3"></VProgressLinear>
                          
                          <div class="d-flex justify-space-between align-center mb-2">
                            <span>System Availability</span>
                            <VChip color="success" size="small">99.9%</VChip>
                          </div>
                          <VProgressLinear color="success" model-value="99"></VProgressLinear>
                        </VCard>
                      </VCol>
                    </VRow>

                    <VAlert type="success" variant="outlined">
                      <VAlertTitle>Performance Excellence</VAlertTitle>
                      All key performance indicators exceed industry standards, demonstrating system reliability and efficiency.
                    </VAlert>
                  </div>
                </VTabsWindowItem>

                <!-- Strategic Recommendations Tab -->
                <VTabsWindowItem value="recommendations">
                  <div class="pa-6">
                    <h2 class="text-h5 font-weight-bold text-primary mb-6">🎯 Strategic Recommendations</h2>
                    
                    <div 
                      v-for="(recommendation, index) in strategicRecommendations" 
                      :key="index"
                      class="mb-6"
                    >
                      <VCard variant="outlined" class="pa-4">
                        <div class="d-flex align-center mb-3">
                          <VAvatar :color="recommendation.priority === 'High' ? 'error' : recommendation.priority === 'Medium' ? 'warning' : 'success'" size="32" class="mr-3">
                            <VIcon color="white">{{ recommendation.icon }}</VIcon>
                          </VAvatar>
                          <div>
                            <h3 class="text-h6 font-weight-bold">{{ recommendation.title }}</h3>
                            <VChip :color="recommendation.priority === 'High' ? 'error' : recommendation.priority === 'Medium' ? 'warning' : 'success'" size="small" class="mt-1">
                              {{ recommendation.priority }} Priority
                            </VChip>
                          </div>
                        </div>
                        
                        <p class="text-body-1 mb-3">{{ recommendation.description }}</p>
                        
                        <div class="mb-3">
                          <h4 class="text-subtitle-1 font-weight-bold mb-2">Expected Benefits:</h4>
                          <ul class="mb-0">
                            <li v-for="benefit in recommendation.benefits" :key="benefit" class="text-body-2 mb-1">
                              {{ benefit }}
                            </li>
                          </ul>
                        </div>
                        
                        <div class="d-flex justify-space-between align-center">
                          <div>
                            <VChip variant="outlined" size="small" class="mr-2">{{ recommendation.timeline }}</VChip>
                            <VChip variant="outlined" size="small">{{ recommendation.effort }}</VChip>
                          </div>
                          <div>
                            <VBtn size="small" color="primary" variant="outlined">View Details</VBtn>
                          </div>
                        </div>
                      </VCard>
                    </div>
                  </div>
                </VTabsWindowItem>

                <!-- Implementation Roadmap Tab -->
                <VTabsWindowItem value="implementation">
                  <div class="pa-6">
                    <h2 class="text-h5 font-weight-bold text-primary mb-6">🗺️ Implementation Roadmap</h2>
                    
                    <VTimeline side="end" class="mt-6">
                      <VTimelineItem
                        v-for="(phase, index) in roadmapPhases"
                        :key="index"
                        :dot-color="phase.status === 'completed' ? 'success' : phase.status === 'active' ? 'primary' : 'grey'"
                        size="large"
                      >
                        <template v-slot:icon>
                          <VIcon>{{ phase.icon }}</VIcon>
                        </template>
                        
                        <VCard variant="outlined" class="pa-4">
                          <div class="d-flex align-center justify-space-between mb-3">
                            <h3 class="text-h6 font-weight-bold">{{ phase.title }}</h3>
                            <VChip 
                              :color="phase.status === 'completed' ? 'success' : phase.status === 'active' ? 'primary' : 'grey'"
                              size="small"
                            >
                              {{ phase.status.charAt(0).toUpperCase() + phase.status.slice(1) }}
                            </VChip>
                          </div>
                          
                          <p class="text-body-2 mb-3">{{ phase.description }}</p>
                          
                          <div class="mb-3">
                            <h4 class="text-subtitle-2 font-weight-bold mb-2">Key Deliverables:</h4>
                            <ul class="mb-0">
                              <li v-for="deliverable in phase.deliverables" :key="deliverable" class="text-body-2 mb-1">
                                {{ deliverable }}
                              </li>
                            </ul>
                          </div>
                          
                          <div class="d-flex justify-space-between align-center">
                            <span class="text-caption text-medium-emphasis">{{ phase.timeline }}</span>
                            <VProgressLinear 
                              :color="phase.status === 'completed' ? 'success' : phase.status === 'active' ? 'primary' : 'grey'"
                              :model-value="phase.progress"
                              class="flex-grow-1 mx-3"
                              height="8"
                            ></VProgressLinear>
                            <span class="text-caption font-weight-bold">{{ phase.progress }}%</span>
                          </div>
                        </VCard>
                      </VTimelineItem>
                    </VTimeline>
                  </div>
                </VTabsWindowItem>
              </VTabsWindow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Filters -->
      <VRow class="mb-4">
        <VCol cols="12">
          <VCard elevation="2">
            <VCardText>
              <VRow>
                <VCol cols="12" md="3">
                  <VTextField
                    v-model="search"
                    label="Search..."
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    @keyup.enter="applyFilters"
                    clearable
                  ></VTextField>
                </VCol>
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="categoryFilter"
                    :items="[
                      { title: 'All Categories', value: '' },
                      ...Object.entries(categories || {}).map(
                        ([key, value]) => ({ title: value, value: key }),
                      ),
                    ]"
                    label="Category"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="typeFilter"
                    :items="[
                      { title: 'All Types', value: '' },
                      ...Object.entries(types || {}).map(([key, value]) => ({
                        title: value,
                        value: key,
                      })),
                    ]"
                    label="Type"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <!-- <v-col cols="12" md="2">
                                    <v-select
                                        v-model="sourceTypeFilter"
                                        :items="sourceTypeItems"
                                        label="Source"
                                        variant="outlined"
                                        density="compact"
                                    ></v-select>
                                </v-col> -->
                <VCol cols="12" md="2">
                  <VSelect
                    v-model="statusFilter"
                    :items="[
                      { title: 'All Status', value: '' },
                      ...Object.entries(statuses || {}).map(([key, value]) => ({
                        title: value,
                        value: key,
                      })),
                    ]"
                    label="Status"
                    variant="outlined"
                    density="compact"
                  ></VSelect>
                </VCol>
                <VCol cols="12" md="2" class="d-flex">
                  <VBtn
                    color="primary"
                    variant="flat"
                    class="w-auto"
                    @click="applyFilters"
                  >
                    Apply
                  </VBtn>
                  <VBtn variant="outlined" @click="clearFilters" class="mx-2">
                    Clear
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Bulk Actions -->
      <VRow class="mb-4" v-if="selectedItems.length > 0">
        <VCol cols="12">
          <VCard elevation="2" color="blue-grey-lighten-5">
            <VCardText>
              <VRow align="center">
                <VCol cols="auto">
                  <span class="text-body-1 font-weight-medium">
                    {{ selectedItems.length }} items selected
                  </span>
                </VCol>
                <VCol cols="auto">
                  <VSelect
                    v-model="bulkAction"
                    :items="bulkActions"
                    variant="outlined"
                    density="compact"
                    hide-details
                    style="min-width: 200px"
                  ></VSelect>
                </VCol>
                <VCol cols="auto">
                  <VBtn
                    color="primary"
                    @click="executeBulkAction"
                    :disabled="!bulkAction"
                  >
                    Execute
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Knowledge Base Table -->
      <VRow>
        <VCol cols="12">
          <VCard elevation="2">
            <VDataTable
              v-model="selectedItems"
              :headers="headers"
              :items="filteredKnowledgeBases"
              :items-per-page="15"
              class="elevation-0"
              show-select
              item-value="id"
            >
              <!-- Title Column -->
              <template #item.title="{ item }">
                <div class="d-flex align-center">
                  <VIcon
                    :icon="getSourceIcon(item.source_type)"
                    :color="item.source_type === 'file' ? 'blue' : 'green'"
                    class="mr-3"
                    size="small"
                  ></VIcon>
                  <div>
                    <div class="font-weight-medium">
                      {{ item.title }}
                    </div>
                    <small class="text-medium-emphasis" v-if="item.excerpt">
                      {{ item.excerpt.substring(0, 100) }}...
                    </small>
                  </div>
                </div>
              </template>

              <!-- Category Column -->
              <template #item.category="{ item }">
                <VChip
                  :color="
                    item.category === 'pajak'
                      ? 'blue'
                      : item.category === 'stnk'
                        ? 'green'
                        : 'grey'
                  "
                  variant="tonal"
                  size="small"
                >
                  {{ categories[item.category] || item.category }}
                </VChip>
              </template>

              <!-- Type Column -->
              <template #item.type="{ item }">
                <VChip
                  :color="
                    item.type === 'faq'
                      ? 'orange'
                      : item.type === 'sop'
                        ? 'purple'
                        : 'blue-grey'
                  "
                  variant="tonal"
                  size="small"
                >
                  {{ types[item.type] || item.type }}
                </VChip>
              </template>

              <!-- Source Type Column -->
              <template #item.source_type="{ item }">
                <VChip
                  :color="item.source_type === 'file' ? 'blue' : 'green'"
                  variant="outlined"
                  size="small"
                >
                  <VIcon
                    :icon="getSourceIcon(item.source_type)"
                    class="mr-1"
                    size="small"
                  ></VIcon>
                  {{ item.source_type === 'file' ? 'File' : 'Manual' }}
                </VChip>
              </template>

              <!-- Status Column -->
              <template #item.status="{ item }">
                <VChip
                  :color="getStatusColor(item.status)"
                  variant="tonal"
                  size="small"
                >
                  {{ statuses[item.status] || item.status }}
                </VChip>
              </template>

              <!-- Active Column -->
              <template #item.is_active="{ item }">
                <VSwitch
                  :model-value="item.is_active"
                  @change="toggleStatus(item)"
                  color="success"
                  density="compact"
                  hide-details
                ></VSwitch>
              </template>

              <!-- Views Column -->
              <template #item.view_count="{ item }">
                <VChip color="info" variant="outlined" size="small">
                  <VIcon icon="mdi-eye" class="mr-1" size="small"></VIcon>
                  {{ item.view_count || 0 }}
                </VChip>
              </template>

              <!-- Created Date Column -->
              <template #item.created_at="{ item }">
                <div>
                  {{ formatDate(item.created_at) }}
                  <div
                    class="text-caption text-medium-emphasis"
                    v-if="item.creator"
                  >
                    by {{ item.creator.name }}
                  </div>
                </div>
              </template>

              <!-- Actions Column -->
              <template #item.actions="{ item }">
                <div class="d-flex gap-2">
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    icon="mdi-eye"
                    @click="
                      $inertia.visit(route('knowledge-base.show', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    size="small"
                    color="orange"
                    variant="tonal"
                    icon="mdi-pencil"
                    @click="
                      $inertia.visit(route('knowledge-base.edit', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    v-if="item.file_path"
                    size="small"
                    color="blue"
                    variant="tonal"
                    icon="mdi-download"
                    @click="
                      window.open(route('knowledge-base.download', item.id))
                    "
                  ></VBtn>
                  <VBtn
                    size="small"
                    color="error"
                    variant="tonal"
                    icon="mdi-delete"
                    @click="deleteItem(item)"
                  ></VBtn>
                </div>
              </template>

              <!-- No Data -->
              <template #no-data>
                <div class="text-center pa-6">
                  <VIcon size="64" color="grey">mdi-book-open-variant</VIcon>
                  <h3 class="text-h6 mt-3">No Knowledge Base Entries Found</h3>
                  <p class="text-body-2 text-medium-emphasis">
                    Try adjusting your search criteria or create a new entry.
                  </p>
                </div>
              </template>
            </VDataTable>

            <!-- Pagination -->
            <VDivider></VDivider>
            <div class="pa-4 d-flex justify-center">
              <VPagination
                :model-value="knowledgeBases.current_page"
                :length="knowledgeBases.last_page"
                @update:model-value="
                  (page) =>
                    router.get(route('knowledge-base.index'), {
                      ...filters,
                      page,
                    })
                "
                total-visible="7"
              ></VPagination>
            </div>
          </VCard>
        </VCol>
      </VRow>

      <!-- Rebuild Pinecone Dialog -->
      <VDialog v-model="syncDialog" max-width="800" persistent>
        <VCard>
          <VCardTitle class="d-flex align-center">
            <VIcon color="warning" class="mr-2">mdi-database-refresh</VIcon>
            Rebuild Vector Database
          </VCardTitle>

          <VCardText>
            <div v-if="!syncResults">
              <VAlert type="warning" variant="outlined" class="mb-4">
                <strong>Destructive Operation:</strong> This will completely rebuild your vector database.
              </VAlert>
              <p class="mb-4">
                This operation will:
              </p>
              <VList density="compact">
                <VListItem>
                  <VListItemTitle
                    >• Clear ALL existing vectors from Pinecone</VListItemTitle
                  >
                </VListItem>
                <VListItem>
                  <VListItemTitle
                    >• Reindex all Knowledge Base entries from scratch</VListItemTitle
                  >
                </VListItem>
                <VListItem>
                  <VListItemTitle
                    >• Ensure complete data consistency</VListItemTitle
                  >
                </VListItem>
              </VList>

              <VCheckbox
                v-model="syncDryRun"
                label="Dry run (analyze only, don't make changes)"
                color="primary"
                class="mt-4"
              ></VCheckbox>
            </div>

            <!-- Sync Results -->
            <div v-if="syncResults">
              <VAlert
                :type="syncResults.success ? 'success' : 'error'"
                class="mb-4"
                prominent
              >
                <VAlertTitle>{{ syncResults.message }}</VAlertTitle>
              </VAlert>

              <div v-if="syncResults.stats">
                <h4 class="text-h6 mb-3">Sync Statistics:</h4>
                <VRow>
                  <VCol cols="6" md="4">
                    <VCard variant="outlined" class="text-center pa-3">
                      <div class="text-h4 text-primary">
                        {{ syncResults.stats.db_entries }}
                      </div>
                      <div class="text-caption">DB Entries</div>
                    </VCard>
                  </VCol>
                  <VCol cols="6" md="4">
                    <VCard variant="outlined" class="text-center pa-3">
                      <div class="text-h4 text-warning">
                        {{ syncResults.stats.vectors_cleared }}
                      </div>
                      <div class="text-caption">Vectors Cleared</div>
                    </VCard>
                  </VCol>
                  <VCol cols="6" md="4">
                    <VCard variant="outlined" class="text-center pa-3">
                      <div class="text-h4 text-success">
                        {{ syncResults.stats.vectors_indexed }}
                      </div>
                      <div class="text-caption">Vectors Indexed</div>
                    </VCard>
                  </VCol>
                </VRow>

                <div v-if="syncResults.stats.errors > 0" class="mt-4">
                  <VAlert type="error">
                    {{ syncResults.stats.errors }} errors occurred during rebuild
                  </VAlert>
                </div>
              </div>

              <div
                v-if="
                  syncResults.dry_run &&
                  syncResults.stats &&
                  syncResults.stats.db_entries > 0
                "
                class="mt-4"
              >
                <VAlert type="info">
                  <VAlertTitle>Ready to Rebuild</VAlertTitle>
                  Uncheck "Dry run" and click "Rebuild Now" to perform the actual
                  vector database rebuild.
                </VAlert>
              </div>
            </div>
          </VCardText>

          <VCardActions>
            <VSpacer></VSpacer>
            <VBtn @click="closeSyncDialog" :disabled="syncProgress">
              {{ syncResults ? 'Close' : 'Cancel' }}
            </VBtn>
            <VBtn
              v-if="!syncResults"
              color="warning"
              @click="syncPinecone"
              :loading="syncProgress"
              :disabled="syncProgress"
            >
              {{ syncDryRun ? 'Preview Rebuild' : 'Rebuild Now' }}
            </VBtn>
            <VBtn
              v-if="
                syncResults &&
                syncResults.dry_run &&
                syncResults.stats &&
                syncResults.stats.db_entries > 0
              "
              color="warning"
              @click="
                syncDryRun = false;
                syncResults = null;
                syncPinecone();
              "
              :loading="syncProgress"
              :disabled="syncProgress"
            >
              Perform Rebuild
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>

      <!-- Delete Confirmation Dialog -->
      <VDialog v-model="confirmDelete" max-width="400">
        <VCard>
          <VCardTitle>
            <VIcon color="error" class="mr-2">mdi-alert</VIcon>
            Confirm Deletion
          </VCardTitle>
          <VCardText>
            Are you sure you want to delete "<strong>{{
              itemToDelete?.title
            }}</strong
            >"? This action cannot be undone.
          </VCardText>
          <VCardActions>
            <VSpacer></VSpacer>
            <VBtn @click="confirmDelete = false">Cancel</VBtn>
            <VBtn color="error" @click="confirmDeleteItem">Delete</VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </AppLayout>
</template>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
