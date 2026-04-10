<template>
  <AppLayout title="AI Chat Analytics">
    <div id="tour-analytics-page" class="p-6">
      <div id="tour-analytics-filter" class="flex gap-4 mb-4">
        <div class="w-1/2">
          <label class="block text-sm text-gray-600"
            >Start date (dd/mm/yyyy)</label
          >
          <input
            ref="startFlat"
            type="text"
            v-model="startDisplay"
            placeholder="dd/mm/yyyy"
            class="mt-1 p-2 border rounded w-full bg-white"
          />
        </div>
        <div class="w-1/2">
          <label class="block text-sm text-gray-600"
            >End date (dd/mm/yyyy)</label
          >
          <input
            ref="endFlat"
            type="text"
            v-model="endDisplay"
            placeholder="dd/mm/yyyy"
            class="mt-1 p-2 border rounded w-full bg-white"
          />
        </div>
      </div>

      <div id="tour-analytics-actions" class="flex gap-3 items-center">
        <button
          @click="confirmStart"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded"
        >
          Start Analysis
        </button>
        <!-- <button @click="loadReports" class="px-4 py-2 bg-gray-200 rounded">
          Refresh Reports
        </button> -->
        <label class="ml-4 inline-flex items-center text-sm text-gray-600">
          <input type="checkbox" v-model="fastMode" class="mr-2" />
          Fast (sample)
        </label>

        <!-- pre-count box -->
        <div
          v-if="preCount"
          class="ml-6 border-2 border-red-500 text-red-600 px-3 py-2 rounded"
        >
          {{ preCount.message_count }} messages Found
        </div>

        <!-- Debug indicator -->
        <!-- <div class="ml-6 text-xs bg-yellow-100 px-2 py-1 rounded">
          Loading: {{ loading }} | ShowLoading: {{ showLoading }}
        </div> -->
        <!-- <div class="mb-3 text-sm text-gray-700" v-if="preCount">
          Data in range: {{ preCount.chat_count }} chats,
          {{ preCount.message_count }} messages
        </div> -->
      </div>

      <div
        v-if="
          reportSummary &&
          reportSummary.status === 'completed' &&
          reportSummary.summary_json
        "
        id="tour-analytics-insights" class="mt-6 bg-white p-4 rounded shadow"
      >
        <h3 class="font-medium mb-3">AI Insights</h3>
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
          <div class="col-span-1 lg:col-span-1">
            <h4 class="font-medium mb-2">Chats per Category</h4>
            <div
              v-if="
                reportSummary?.summary_json?.categories &&
                reportSummary.summary_json.categories.length
              "
              class="mb-4"
            >
              <ApexChart
                type="bar"
                height="520"
                :options="categoryChartOptions"
                :series="categorySeries"
              />
            </div>
            <div v-else class="text-sm text-gray-500 mb-4">
              No category data.
            </div>
          </div>

          <!-- <div>
            <h4 class="font-medium">Sentiments</h4>
            <div class="mt-2">
              <div
                v-for="(v, k) in reportSummary.summary_json?.sentiments || {}"
                :key="k"
                class="flex items-center justify-between text-sm mb-2"
              >
                <div class="capitalize">{{ k }}</div>
                <div class="text-gray-600">{{ v }}</div>
              </div>
            </div>
          </div> -->
        </div>

        <div class="mt-6">
          <div
            class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 rounded-t-lg"
          >
            <h4 class="font-bold text-lg flex items-center">
              <svg
                class="w-6 h-6 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                ></path>
              </svg>
              Professional AI Analysis & Strategic Intelligence
            </h4>
            <p class="text-blue-100 text-sm mt-1">
              Comprehensive analytical insights for organizational
              decision-making
            </p>
          </div>
          <div
            class="bg-white border border-gray-200 rounded-b-lg p-6 shadow-lg"
          >
            <!-- Executive Summary Header -->
            <div
              class="mb-6 p-4 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg border-l-4 border-blue-500"
            >
              <h5 class="font-bold text-gray-800 mb-2 flex items-center">
                📊 Executive Summary
                <span
                  class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                  >AI-Generated</span
                >
              </h5>
              <p class="text-sm text-gray-600">
                Strategic insights derived from comprehensive data analysis and
                pattern recognition
              </p>
            </div>

            <!-- Key Insights Content -->
            <div class="space-y-4">
              <!-- Render as Markdown with proper formatting -->
              <div
                class="prose prose-sm max-w-none text-gray-800"
                v-html="renderMarkdown(combinedInsightText)"
              ></div>
            </div>

            <!-- Analysis Metadata Footer -->
            <div class="mt-6 pt-4 border-t border-gray-200">
              <div
                class="flex items-center justify-between text-xs text-gray-500"
              >
                <div class="flex items-center space-x-4">
                  <span class="flex items-center">
                    <svg
                      class="w-4 h-4 mr-1"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      ></path>
                    </svg>
                    Generated:
                    {{
                      new Date(reportSummary.created_at).toLocaleDateString(
                        'id-ID',
                        {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                        },
                      )
                    }}
                  </span>
                  <span class="flex items-center">
                    <svg
                      class="w-4 h-4 mr-1"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                      ></path>
                    </svg>
                    {{ reportSummary.chat_count }} conversations analyzed
                  </span>
                  <span
                    v-if="reportSummary.summary_json?.categories?.length"
                    class="flex items-center"
                  >
                    <svg
                      class="w-4 h-4 mr-1"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                      ></path>
                    </svg>
                    {{ reportSummary.summary_json.categories.length }} topic
                    categories
                  </span>
                </div>
                <div class="flex items-center">
                  <span
                    class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium"
                  >
                    ✓ Analysis Complete
                  </span>
                </div>
              </div>
            </div>

            <!-- Professional Disclaimer -->
            <div class="mt-4 p-3 bg-gray-50 rounded-lg border">
              <p class="text-xs text-gray-600 flex items-start">
                <svg
                  class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  ></path>
                </svg>
                <span
                  ><strong>Professional Note:</strong> This analysis is
                  generated using advanced AI algorithms and should be
                  considered alongside domain expertise and organizational
                  context. Recommendations require validation through proper
                  implementation planning and stakeholder consultation.</span
                >
              </p>
            </div>
          </div>
        </div>

        <div class="mt-8">
          <h4 class="font-medium mb-4">
            AI Analysis (Top 3 Topics Strategies)
          </h4>
          <div v-if="reportSummary.summary_json?.top_topics_strategies">
            <div
              v-for="(s, key) in reportSummary.summary_json
                .top_topics_strategies"
              :key="key"
              class="mb-6 border rounded-lg p-4 bg-white shadow-sm"
            >
              <!-- Topic Header -->
              <div class="border-b border-gray-200 pb-3 mb-4">
                <div class="flex items-center justify-between">
                  <h5 class="text-lg font-semibold text-gray-800">
                    📊 {{ s.topic_name || s.label || s.topic_key }}
                  </h5>
                  <div
                    class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full"
                    v-if="s.topic_count || s.count"
                  >
                    {{ s.topic_count || s.count }} permintaan
                  </div>
                </div>

                <!-- Topic Overview -->
                <div
                  v-if="s.topic_overview"
                  class="mt-3 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r"
                >
                  <p class="text-sm text-blue-800 leading-relaxed">
                    <strong>📋 Analisis Overview:</strong>
                    {{ s.topic_overview }}
                  </p>
                </div>

                <!-- Top Questions Section -->
                <div
                  v-if="s.top_questions && s.top_questions.length"
                  class="mt-4 p-4 bg-green-50 border-l-4 border-green-400 rounded-r"
                >
                  <h6
                    class="font-semibold text-green-800 mb-3 flex items-center"
                  >
                    <svg
                      class="w-5 h-5 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      ></path>
                    </svg>
                    5 Informasi Teratas yang Paling Sering Ditanyakan
                  </h6>
                  <div class="space-y-2">
                    <div
                      v-for="(question, qIdx) in s.top_questions.slice(0, 5)"
                      :key="qIdx"
                      class="flex items-start"
                    >
                      <span
                        class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full mr-3 mt-0.5 flex-shrink-0"
                      >
                        {{ qIdx + 1 }}
                      </span>
                      <p class="text-sm text-green-700">{{ question }}</p>
                    </div>
                  </div>
                </div>

                <!-- Further Analysis Section -->
                <div
                  v-if="s.further_analysis"
                  class="mt-4 p-4 bg-purple-50 border-l-4 border-purple-400 rounded-r"
                >
                  <h6
                    class="font-semibold text-purple-800 mb-3 flex items-center"
                  >
                    <svg
                      class="w-5 h-5 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                      ></path>
                    </svg>
                    Analisis Mendalam
                  </h6>
                  <p class="text-sm text-purple-700 leading-relaxed">
                    {{ s.further_analysis }}
                  </p>
                </div>

                <!-- Example Chat Messages Section -->
                <div
                  v-if="
                    s.example_chat_messages && s.example_chat_messages.length
                  "
                  class="mt-4 p-4 bg-orange-50 border-l-4 border-orange-400 rounded-r"
                >
                  <h6
                    class="font-semibold text-orange-800 mb-3 flex items-center"
                  >
                    <svg
                      class="w-5 h-5 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                      ></path>
                    </svg>
                    7 Contoh Pesan Chat Wajib Pajak
                  </h6>
                  <div class="space-y-3">
                    <div
                      v-for="(message, mIdx) in s.example_chat_messages.slice(
                        0,
                        7,
                      )"
                      :key="mIdx"
                      class="bg-white border border-orange-200 rounded-lg p-3 shadow-sm"
                    >
                      <div class="flex items-start">
                        <div
                          class="bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-1 rounded-full mr-3 mt-0.5 flex-shrink-0"
                        >
                          {{ mIdx + 1 }}
                        </div>
                        <div class="flex-1">
                          <div
                            class="bg-gray-100 text-gray-800 text-sm p-2 rounded-lg italic"
                          >
                            "{{ message }}"
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Implementation Steps -->
              <div class="space-y-3">
                <h6 class="font-medium text-gray-700 mb-3">
                  🎯 5 Langkah Implementasi Strategis:
                </h6>
                <div class="mt-2 text-sm space-y-2">
                  <p
                    v-for="(p, i) in paragraphize(s.detailed_strategy)"
                    :key="i"
                  >
                    {{ p }}
                  </p>
                </div>
                <div class="mt-3">
                  <div
                    class="font-medium text-sm flex items-center justify-between"
                  >
                    <span>Implementation Steps (Top 5 Key Actions)</span>
                    <span
                      class="text-xs text-gray-500 bg-blue-50 px-2 py-1 rounded"
                      >Strategic Priority</span
                    >
                  </div>
                  <div
                    v-if="
                      Array.isArray(s.implementation_steps) &&
                      s.implementation_steps.length
                    "
                    class="space-y-4 mt-3"
                  >
                    <div
                      v-for="(st, idx) in s.implementation_steps.slice(0, 5)"
                      :key="idx"
                      class="text-sm border-l-4 border-blue-500 rounded bg-gradient-to-r from-blue-50 to-white p-4 shadow-sm"
                    >
                      <template v-if="typeof st === 'string'">
                        <div class="flex items-center mb-2">
                          <div
                            class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3"
                          >
                            {{ idx + 1 }}
                          </div>
                          <div class="font-semibold text-blue-900">
                            Strategic Action {{ idx + 1 }}
                          </div>
                        </div>
                        <div class="ml-11 whitespace-pre-line text-gray-700">
                          {{ st }}
                        </div>
                      </template>
                      <template v-else>
                        <div class="flex items-start justify-between mb-2">
                          <div class="flex items-center">
                            <div
                              class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3"
                            >
                              {{ idx + 1 }}
                            </div>
                            <div class="font-semibold text-blue-900">
                              {{ st.title || `Strategic Action ${idx + 1}` }}
                            </div>
                          </div>
                          <div
                            class="text-xs text-gray-600 bg-white px-2 py-1 rounded border"
                            v-if="st.estimated_time || st.effort"
                          >
                            <span
                              v-if="st.estimated_time"
                              class="font-medium"
                              >{{ st.estimated_time }}</span
                            >
                            <span v-if="st.estimated_time && st.effort">
                              •
                            </span>
                            <span
                              v-if="st.effort"
                              class="text-orange-600 font-medium"
                              >{{ st.effort }} effort</span
                            >
                          </div>
                        </div>
                        <div
                          class="ml-11 whitespace-pre-line text-gray-700 leading-relaxed"
                        >
                          {{ st.description || '' }}
                        </div>
                        <div
                          v-if="st.example"
                          class="ml-11 mt-3 text-xs text-gray-600"
                        >
                          <div
                            class="uppercase tracking-wide font-semibold text-green-700 mb-1"
                          >
                            💡 Implementation Example
                          </div>
                          <pre
                            class="whitespace-pre-wrap bg-green-50 border border-green-200 rounded p-3 text-green-800"
                            >{{ st.example }}</pre
                          >
                        </div>
                      </template>
                    </div>
                    <div
                      v-if="s.implementation_steps.length > 5"
                      class="text-center"
                    >
                      <div
                        class="text-xs text-gray-500 bg-gray-100 px-3 py-2 rounded-full inline-block"
                      >
                        + {{ s.implementation_steps.length - 5 }} additional
                        strategic actions available in full analysis
                      </div>
                    </div>
                  </div>
                  <div
                    v-else
                    class="text-sm text-gray-500 mt-1 bg-yellow-50 border border-yellow-200 rounded p-3"
                  >
                    📋 Implementation roadmap pending AI analysis completion.
                  </div>
                </div>
              </div>
            </div>
            <!-- <div v-else class="text-sm text-gray-500">
              No top-topic strategies available.
            </div> -->
          </div>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-medium mb-2">Recent Reports</h3>
        <ul>
          <li v-for="r in reports" :key="r.id" class="p-3 border-b">
            <div class="flex justify-between">
              <div>
                <div class="font-medium">Report #{{ r.id }}</div>
                <div class="text-sm text-gray-500">
                  {{ humanDate(r.start_date) }} → {{ humanDate(r.end_date) }}
                </div>
              </div>
              <div class="text-right">
                <div>{{ r.chat_count }} chats</div>
                <div class="text-sm">{{ r.status }}</div>
                <div class="mt-2">
                  <button
                    @click="openReport(r.id)"
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded"
                  >
                    View report
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>
        <div
          class="flex items-center justify-between mt-3"
          v-if="reportLastPage > 1"
        >
          <div class="text-sm text-gray-600">
            Page {{ reportPage }} of {{ reportLastPage }} —
            {{ reportTotal }} total
          </div>
          <div class="flex items-center gap-2">
            <button
              class="px-3 py-1 rounded bg-gray-200 text-sm"
              :disabled="reportPage <= 1"
              @click="loadReports(reportPage - 1)"
            >
              Prev
            </button>
            <button
              class="px-3 py-1 rounded bg-gray-200 text-sm"
              :disabled="reportPage >= reportLastPage"
              @click="loadReports(reportPage + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Report modal -->
      <div
        v-if="modalOpen"
        class="fixed inset-0 bg-black/50 flex items-start justify-center p-6 z-[4200]"
      >
        <div
          class="bg-white w-full max-w-5xl rounded shadow-lg overflow-auto max-h-[90vh] z-[4300] mt-12"
        >
          <div class="flex items-center justify-between p-4 border-b">
            <div class="font-semibold">
              Report #{{ modalReport.id }} —
              {{ humanDate(modalReport.start_date) }} →
              {{ humanDate(modalReport.end_date) }}
            </div>
            <button @click="closeModal" class="px-3 py-1 bg-gray-200 rounded">
              Close
            </button>
          </div>
          <div class="p-4">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
              <div>
                <h4 class="font-medium mb-2">Charts</h4>
                <div v-if="modalReport.summary_json?.categories?.length">
                  <ApexChart
                    type="bar"
                    height="420"
                    :options="modalCategoryOptions"
                    :series="modalCategorySeries"
                  />
                </div>
                <div v-else class="text-sm text-gray-500">
                  No category data.
                </div>
              </div>
            </div>
            <div class="mt-6">
              <div
                class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 rounded-t-lg"
              >
                <h4 class="font-bold text-lg flex items-center">
                  <svg
                    class="w-6 h-6 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                    ></path>
                  </svg>
                  Professional AI Analysis & Strategic Intelligence
                </h4>
                <p class="text-blue-100 text-sm mt-1">
                  Comprehensive analytical insights for organizational
                  decision-making
                </p>
              </div>
              <div
                class="bg-white border border-gray-200 rounded-b-lg p-6 shadow-lg"
              >
                <!-- Executive Summary Header -->
                <div
                  class="mb-6 p-4 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg border-l-4 border-blue-500"
                >
                  <h5 class="font-bold text-gray-800 mb-2 flex items-center">
                    📊 Executive Summary
                    <span
                      class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                      >AI-Generated</span
                    >
                  </h5>
                  <p class="text-sm text-gray-600">
                    Strategic insights derived from comprehensive data analysis
                    and pattern recognition
                  </p>
                </div>

                <!-- Key Insights Content -->
                <div class="space-y-4">
                  <!-- Render as Markdown with proper formatting -->
                  <div
                    class="prose prose-sm max-w-none text-gray-800"
                    v-html="renderMarkdown(modalCombinedInsightText)"
                  ></div>
                </div>

                <!-- Analysis Metadata Footer -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                  <div
                    class="flex items-center justify-between text-xs text-gray-500"
                  >
                    <div class="flex items-center space-x-4">
                      <span class="flex items-center">
                        <svg
                          class="w-4 h-4 mr-1"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                          ></path>
                        </svg>
                        Generated:
                        {{
                          new Date(modalReport.created_at).toLocaleDateString(
                            'id-ID',
                            {
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric',
                              hour: '2-digit',
                              minute: '2-digit',
                            },
                          )
                        }}
                      </span>
                      <span class="flex items-center">
                        <svg
                          class="w-4 h-4 mr-1"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                          ></path>
                        </svg>
                        {{ modalReport.chat_count }} conversations analyzed
                      </span>
                      <span
                        v-if="modalReport.summary_json?.categories?.length"
                        class="flex items-center"
                      >
                        <svg
                          class="w-4 h-4 mr-1"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                          ></path>
                        </svg>
                        {{ modalReport.summary_json.categories.length }} topic
                        categories
                      </span>
                    </div>
                    <div class="flex items-center">
                      <span
                        class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium"
                      >
                        ✓ Analysis Complete
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Professional Disclaimer -->
                <div class="mt-4 p-3 bg-gray-50 rounded-lg border">
                  <p class="text-xs text-gray-600 flex items-start">
                    <svg
                      class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      ></path>
                    </svg>
                    <span
                      ><strong>Professional Note:</strong> This analysis is
                      generated using advanced AI algorithms and should be
                      considered alongside domain expertise and organizational
                      context. Recommendations require validation through proper
                      implementation planning and stakeholder
                      consultation.</span
                    >
                  </p>
                </div>
              </div>
            </div>
            <div>
              <h4 class="font-medium mb-4 mt-8">
                AI Analysis (Top 3 Topics Strategies)
              </h4>
              <div v-if="modalReport.summary_json?.top_topics_strategies">
                <div
                  v-for="(s, key) in modalReport.summary_json
                    .top_topics_strategies"
                  :key="key"
                  class="mb-6 border rounded-lg p-4 bg-white shadow-sm"
                >
                  <!-- Topic Header -->
                  <div class="border-b border-gray-200 pb-3 mb-4">
                    <div class="flex items-center justify-between">
                      <h5 class="text-lg font-semibold text-gray-800">
                        📊 {{ s.topic_name || s.label || s.topic_key }}
                      </h5>
                      <div
                        class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full"
                        v-if="s.topic_count || s.count"
                      >
                        {{ s.topic_count || s.count }} permintaan
                      </div>
                    </div>

                    <!-- Topic Overview -->
                    <div
                      v-if="s.topic_overview"
                      class="mt-3 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-r"
                    >
                      <p class="text-sm text-blue-800 leading-relaxed">
                        <strong>📋 Analisis Overview:</strong>
                        {{ s.topic_overview }}
                      </p>
                    </div>

                    <!-- Top Questions Section -->
                    <div
                      v-if="s.top_questions && s.top_questions.length"
                      class="mt-4 p-4 bg-green-50 border-l-4 border-green-400 rounded-r"
                    >
                      <h6
                        class="font-semibold text-green-800 mb-3 flex items-center"
                      >
                        <svg
                          class="w-5 h-5 mr-2"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                          ></path>
                        </svg>
                        5 Informasi Teratas yang Paling Sering Ditanyakan
                      </h6>
                      <div class="space-y-2">
                        <div
                          v-for="(question, qIdx) in s.top_questions.slice(
                            0,
                            5,
                          )"
                          :key="qIdx"
                          class="flex items-start"
                        >
                          <span
                            class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full mr-3 mt-0.5 flex-shrink-0"
                          >
                            {{ qIdx + 1 }}
                          </span>
                          <p class="text-sm text-green-700">{{ question }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- Further Analysis Section -->
                    <div
                      v-if="s.further_analysis"
                      class="mt-4 p-4 bg-purple-50 border-l-4 border-purple-400 rounded-r"
                    >
                      <h6
                        class="font-semibold text-purple-800 mb-3 flex items-center"
                      >
                        <svg
                          class="w-5 h-5 mr-2"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                          ></path>
                        </svg>
                        Analisis Mendalam
                      </h6>
                      <p class="text-sm text-purple-700 leading-relaxed">
                        {{ s.further_analysis }}
                      </p>
                    </div>

                    <!-- Example Chat Messages Section -->
                    <div
                      v-if="
                        s.example_chat_messages &&
                        s.example_chat_messages.length
                      "
                      class="mt-4 p-4 bg-orange-50 border-l-4 border-orange-400 rounded-r"
                    >
                      <h6
                        class="font-semibold text-orange-800 mb-3 flex items-center"
                      >
                        <svg
                          class="w-5 h-5 mr-2"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                          ></path>
                        </svg>
                        7 Contoh Pesan Chat Wajib Pajak
                      </h6>
                      <div class="space-y-3">
                        <div
                          v-for="(
                            message, mIdx
                          ) in s.example_chat_messages.slice(0, 7)"
                          :key="mIdx"
                          class="bg-white border border-orange-200 rounded-lg p-3 shadow-sm"
                        >
                          <div class="flex items-start">
                            <div
                              class="bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-1 rounded-full mr-3 mt-0.5 flex-shrink-0"
                            >
                              {{ mIdx + 1 }}
                            </div>
                            <div class="flex-1">
                              <div
                                class="bg-gray-100 text-gray-800 text-sm p-2 rounded-lg italic"
                              >
                                "{{ message }}"
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Implementation Steps -->
                  <div class="space-y-3">
                    <h6 class="font-medium text-gray-700 mb-3">
                      🎯 5 Langkah Implementasi Strategis:
                    </h6>
                    <div class="mt-3">
                      <div
                        class="font-medium text-sm flex items-center justify-between"
                      >
                        <span>Implementation Steps (Top 5 Key Actions)</span>
                        <span
                          class="text-xs text-gray-500 bg-blue-50 px-2 py-1 rounded"
                          >Strategic Priority</span
                        >
                      </div>
                      <div
                        v-if="
                          Array.isArray(s.implementation_steps) &&
                          s.implementation_steps.length
                        "
                        class="space-y-4 mt-3"
                      >
                        <div
                          v-for="(st, idx) in s.implementation_steps.slice(
                            0,
                            5,
                          )"
                          :key="idx"
                          class="text-sm border-l-4 border-blue-500 rounded bg-gradient-to-r from-blue-50 to-white p-4 shadow-sm"
                        >
                          <template v-if="typeof st === 'string'">
                            <div class="flex items-center mb-2">
                              <div
                                class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3"
                              >
                                {{ idx + 1 }}
                              </div>
                              <div class="font-semibold text-blue-900">
                                Strategic Action {{ idx + 1 }}
                              </div>
                            </div>
                            <div
                              class="ml-11 whitespace-pre-line text-gray-700"
                            >
                              {{ st }}
                            </div>
                          </template>
                          <template v-else>
                            <div class="flex items-start justify-between mb-2">
                              <div class="flex items-center">
                                <div
                                  class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3"
                                >
                                  {{ idx + 1 }}
                                </div>
                                <div class="font-semibold text-blue-900">
                                  {{
                                    st.title || `Strategic Action ${idx + 1}`
                                  }}
                                </div>
                              </div>
                              <div
                                class="text-xs text-gray-600 bg-white px-2 py-1 rounded border"
                                v-if="st.estimated_time || st.effort"
                              >
                                <span
                                  v-if="st.estimated_time"
                                  class="font-medium"
                                  >{{ st.estimated_time }}</span
                                >
                                <span v-if="st.estimated_time && st.effort">
                                  •
                                </span>
                                <span
                                  v-if="st.effort"
                                  class="text-orange-600 font-medium"
                                  >{{ st.effort }} effort</span
                                >
                              </div>
                            </div>
                            <div
                              class="ml-11 whitespace-pre-line text-gray-700 leading-relaxed"
                            >
                              {{ st.description || '' }}
                            </div>
                            <div
                              v-if="st.example"
                              class="ml-11 mt-3 text-xs text-gray-600"
                            >
                              <div
                                class="uppercase tracking-wide font-semibold text-green-700 mb-1"
                              >
                                💡 Implementation Example
                              </div>
                              <pre
                                class="whitespace-pre-wrap bg-green-50 border border-green-200 rounded p-3 text-green-800"
                                >{{ st.example }}</pre
                              >
                            </div>
                          </template>
                        </div>
                        <div
                          v-if="s.implementation_steps.length > 5"
                          class="text-center"
                        >
                          <div
                            class="text-xs text-gray-500 bg-gray-100 px-3 py-2 rounded-full inline-block"
                          >
                            + {{ s.implementation_steps.length - 5 }} additional
                            strategic actions available in full analysis
                          </div>
                        </div>
                      </div>
                      <div
                        v-else
                        class="text-sm text-gray-500 mt-1 bg-yellow-50 border border-yellow-200 rounded p-3"
                      >
                        📋 Implementation roadmap pending AI analysis
                        completion.
                      </div>
                    </div>
                  </div>
                </div>
                <!-- <div v-else class="text-sm text-gray-500">
                  No top-topic strategies available.
                </div> -->
              </div>
              <div
                v-if="modalReport.summary_json?.detailed_analysis"
                class="mt-6"
              >
                <h4 class="font-medium">Analisis Terperinci (AI)</h4>
                <div
                  class="mt-2 whitespace-pre-line text-sm text-gray-800 bg-gray-50 p-3 rounded"
                >
                  {{ modalReport.summary_json.detailed_analysis }}
                </div>
              </div>
              <div v-if="modalPerTopicInsightsLimited.length" class="mt-6">
                <h4 class="font-medium">Insight Per Topik (AI)</h4>
                <div class="mt-3 space-y-4">
                  <div
                    v-for="(p, idx) in modalPerTopicInsightsLimited"
                    :key="p.topic_key || p.label || idx"
                    class="border rounded p-3"
                  >
                    <div class="font-semibold">
                      {{ p.label || p.topic_key || `Topik ${idx + 1}` }}
                    </div>
                    <div v-if="p.count" class="text-xs text-gray-500">
                      Count: {{ p.count }}
                    </div>
                    <div class="mt-2 whitespace-pre-line text-sm text-gray-800">
                      {{ p.long_insight || p.insight || p.text }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- BACKUP SIMPLE LOADING POPUP FOR DEBUGGING -->

    <div
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-[9999]"
      style="z-index: 9999 !important"
      v-if="showLoading"
    >
      <div
        class="bg-white p-6 rounded-lg shadow-xl w-96 text-center relative z-[10000]"
        style="z-index: 10000 !important"
      >
        <!-- Close Button -->
        <button
          @click="closeLoadingPopup"
          class="absolute top-2 right-2 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
          title="Close (analysis continues in background)"
        >
          <svg
            class="w-5 h-5 text-gray-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>

        <!-- Loading Icon -->
        <div class="mb-4">
          <svg
            class="mx-auto animate-spin h-12 w-12 text-blue-600"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
              stroke-opacity="0.25"
            />
            <path
              d="M22 12a10 10 0 00-10-10"
              stroke="currentColor"
              stroke-width="4"
              stroke-linecap="round"
            />
          </svg>
        </div>

        <!-- Main Title -->
        <div class="text-lg font-medium mb-2">
          🤖 Analyzing Taxpayer Conversations
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
          <div class="flex justify-between text-sm text-gray-600 mb-2">
            <span>{{ currentProgress.stage }}</span>
            <span>{{ currentProgress.percentage }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div
              class="bg-gradient-to-r from-blue-500 to-purple-500 h-3 rounded-full transition-all duration-500 ease-out"
              :style="{ width: currentProgress.percentage + '%' }"
            ></div>
          </div>
        </div>

        <!-- Current Step -->
        <div class="text-sm text-gray-600 mb-4">
          {{ currentProgress.description }}
        </div>

        <!-- Info Text -->
        <div class="text-sm text-gray-500 mt-2">
          This may take a while. You can close this popup and continue working.
        </div>
        <!-- <div class="text-xs text-gray-400 mt-2">
          💡 You'll get a notification when analysis is complete
        </div> -->
      </div>
    </div>
      <TourButton @start="startTour" />
  </AppLayout>
</template>

<script setup>
import TourButton from '@/Components/TourButton.vue';
import { useTour } from '@/composables/useTour.js';
import {
  ref, onMounted, onUnmounted, computed, watch,
} from 'vue';
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import flatpickr from 'flatpickr';
import { toast } from 'vue3-toastify';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import 'flatpickr/dist/flatpickr.min.css';

const ApexChart = VueApexCharts;

const startDate = ref('');
const endDate = ref('');
const startIso = ref(null); // yyyy-mm-dd
const endIso = ref(null);
// Date objects used by Datepicker
const startDateObj = ref(null);
const endDateObj = ref(null);
const startDateInput = ref(null);
const endDateInput = ref(null);
const startDisplay = ref('');
const endDisplay = ref('');
const startFlat = ref(null);
const endFlat = ref(null);
const reports = ref([]);
const reportSummary = ref(null);
const displayFormat = 'dd/MM/yyyy';
const onIsoChange = () => {
  /* noop - watcher handles updates */
};
const showLoading = ref(false);
const loading = ref(false);
const fastMode = ref(false);
const pollToken = ref(0);
const modalOpen = ref(false);
const modalReport = ref({});
const preCount = ref(null);
const reportPage = ref(1);
const reportPerPage = ref(5);
const reportTotal = ref(0);
const reportLastPage = ref(1);

// Progress tracking for interactive loading
const currentProgress = ref({
  percentage: 0,
  stage: 'Initializing...',
  description: 'Preparing analysis system',
});

const progressStages = [
  {
    percentage: 10,
    stage: 'Initializing...',
    description: 'Preparing analysis system',
  },
  {
    percentage: 25,
    stage: 'Collecting Data',
    description: 'Gathering taxpayer conversations',
  },
  {
    percentage: 40,
    stage: 'Processing Messages',
    description: 'Analyzing chat patterns and topics',
  },
  {
    percentage: 60,
    stage: 'AI Analysis',
    description: 'Generating strategic insights with AI',
  },
  {
    percentage: 80,
    stage: 'Generating Report',
    description: 'Creating professional analytics report',
  },
  {
    percentage: 95,
    stage: 'Finalizing',
    description: 'Preparing results for display',
  },
  {
    percentage: 100,
    stage: 'Complete!',
    description: 'Analysis ready for review',
  },
];

let progressInterval = null;

// Progress simulation functions
const startProgressSimulation = () => {
  currentProgress.value = progressStages[0];
  let currentStageIndex = 0;

  // Clear any existing interval
  if (progressInterval) {
    clearInterval(progressInterval);
  }

  progressInterval = setInterval(() => {
    if (currentStageIndex < progressStages.length - 1) {
      currentStageIndex++;
      currentProgress.value = progressStages[currentStageIndex];

      // Slow down as we approach completion
      if (currentStageIndex >= progressStages.length - 2) {
        clearInterval(progressInterval);
        // Keep at 95% until actual completion
        currentProgress.value = progressStages[progressStages.length - 2];
      }
    }
  }, 2000); // Change stage every 2 seconds
};

const completeProgress = () => {
  if (progressInterval) {
    clearInterval(progressInterval);
  }
  currentProgress.value = progressStages[progressStages.length - 1];

  // Auto-hide after showing completion for 1 second
  setTimeout(() => {
    if (showLoading.value) {
      showLoading.value = false;
    }
  }, 1000);
};

const resetProgress = () => {
  if (progressInterval) {
    clearInterval(progressInterval);
  }
  currentProgress.value = progressStages[0];
};

// Function to close loading popup
const closeLoadingPopup = () => {
  showLoading.value = false;
  resetProgress();
  toast.info(
    "📊 Analysis continues in background. You'll be notified when complete!",
    {
      autoClose: 3000,
      position: 'top-right',
      hideProgressBar: false,
      closeOnClick: true,
      pauseOnHover: true,
      draggable: true,
    },
  );
};

const loadReports = async (page = 1) => {
  const url = `/api/admin/analytics/reports?page=${page}&per_page=${reportPerPage.value}`;
  const res = await fetch(url);
  const j = await res.json();
  reports.value = j.data || [];
  reportPage.value = j.current_page || page;
  reportPerPage.value = j.per_page || reportPerPage.value;
  reportTotal.value = j.total || (j.data ? j.data.length : 0);
  reportLastPage.value = j.last_page || 1;
};

const updatePreCount = async () => {
  preCount.value = null;
  if (!startIso.value || !endIso.value) return;
  if (startIso.value > endIso.value) return;
  try {
    const res = await axios.get('/api/admin/analytics/count', {
      params: { start_date: startIso.value, end_date: endIso.value },
    });
    preCount.value = res.data;
  } catch (e) {
    console.warn('Pre-count failed', e?.message || e);
  }
};

const confirmStart = async () => {
  if (!startIso.value || !endIso.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Incomplete Date Selection',
      text: 'Please select both start and end dates.',
      confirmButtonColor: '#3b82f6',
    });
    return;
  }

  if (startIso.value > endIso.value) {
    await Swal.fire({
      icon: 'error',
      title: 'Invalid Date Range',
      text: 'Start date must be before end date.',
      confirmButtonColor: '#3b82f6',
    });
    return;
  }

  const result = await Swal.fire({
    title: 'Start AI Analytics Analysis?',
    html: `
      <div class="text-left">
        <p class="mb-3"><strong>Analysis Period:</strong></p>
        <p class="text-sm text-gray-600 mb-2">📅 From: <span class="font-semibold">${startDisplay.value}</span></p>
        <p class="text-sm text-gray-600 mb-3">📅 To: <span class="font-semibold">${endDisplay.value}</span></p>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded">
          <p class="text-sm text-blue-800">
            <strong>🤖 AI Analysis will include:</strong><br>
            • Chat conversation patterns<br>
            • Top 3 strategic topics with detailed implementation plans<br>
            • Professional insights and recommendations
          </p>
        </div>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#6b7280',
    confirmButtonText: '🚀 Start Analysis',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: {
      popup: 'text-left',
      title: 'text-lg font-semibold',
      htmlContainer: 'text-sm',
    },
  });

  if (!result.isConfirmed) return;

  console.log('Starting analysis - setting loading states');
  console.log(
    'BEFORE: loading=',
    loading.value,
    'showLoading=',
    showLoading.value,
  );

  loading.value = true;
  showLoading.value = true;

  console.log(
    'AFTER: loading=',
    loading.value,
    'showLoading=',
    showLoading.value,
  );
  console.log('DOM should now show loading popup');

  // Start progress simulation
  startProgressSimulation();

  // Show start notification
  toast.info('🚀 Starting analytics analysis...', {
    autoClose: 2000,
    position: 'top-right',
  });

  try {
    const res = await axios.post('/api/admin/analytics/start', {
      start_date: startIso.value,
      end_date: endIso.value,
      sample: fastMode.value,
    });
    const j = res.data;
    reportSummary.value = j;

    console.log('Analysis response:', j);

    // If sync mode and summary is present, show it immediately
    if (j.processing_mode === 'sync') {
      if (j.summary_json) {
        completeProgress();
        loading.value = false;
        await new Promise((resolve) => setTimeout(resolve, 1500)); // Let user see completion
        showLoading.value = false;
        await loadReports();
        return;
      }
      if (j.status === 'completed') {
        // fetch the saved report to ensure we have summary_json
        const r = await fetch(`/api/admin/analytics/reports/${j.report_id}`);
        reportSummary.value = await r.json();
        completeProgress();
        loading.value = false;
        await new Promise((resolve) => setTimeout(resolve, 1500)); // Let user see completion
        showLoading.value = false;
        await loadReports();
        return;
      }
    }
    // For async mode, keep loading popup and start polling
    pollToken.value++;
    pollReport(j.report_id, pollToken.value);
  } catch (e) {
    console.error('Analysis failed:', e);
    resetProgress();
    loading.value = false;
    showLoading.value = false;
    await Swal.fire({
      icon: 'error',
      title: 'Analysis Failed',
      text: `Failed to start analysis: ${e?.response?.data?.message || e.message}`,
      confirmButtonColor: '#3b82f6',
    });
  }
};

const pollReport = async (id, token) => {
  const myToken = token;

  const tick = async () => {
    // stop if token has changed (a new poll started)
    if (pollToken.value !== myToken) return;
    const r = await fetch(`/api/admin/analytics/reports/${id}`);
    const data = await r.json();
    reportSummary.value = data;
    if (data.status === 'completed' || data.status === 'failed') {
      // If summary_json missing in the immediate response, re-fetch once
      if (!data.summary_json) {
        try {
          await new Promise((r) => setTimeout(r, 500));
          const r2 = await fetch(`/api/admin/analytics/reports/${id}`);
          const d2 = await r2.json();
          reportSummary.value = d2;
        } catch (e) {
          /* ignore */
        }
      }

      // Complete progress and show result
      if (data.status === 'completed') {
        completeProgress();
        await new Promise((resolve) => setTimeout(resolve, 1500)); // Let user see completion
      } else {
        resetProgress();
      }

      showLoading.value = false;

      // Show completion notification
      if (data.status === 'completed') {
        toast.success(
          '🎉 Analytics analysis completed! You can now view the results.',
          {
            autoClose: 5000,
            position: 'top-right',
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
          },
        );
      } else if (data.status === 'failed') {
        toast.error('❌ Analytics analysis failed. Please try again.', {
          autoClose: 5000,
          position: 'top-right',
          hideProgressBar: false,
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true,
        });
      }

      await loadReports();
      return;
    }
    setTimeout(tick, 3000);
  };
  tick();
};

onMounted(() => {
  loadReports();
  // initialize flatpickr on the two inputs
  try {
    if (startFlat.value) {
      flatpickr(startFlat.value, {
        dateFormat: 'd/m/Y',
        allowInput: true,
        defaultDate: startIso.value || null,
        onChange: (selectedDates) => {
          const d = selectedDates[0] || null;
          startDateObj.value = d;
          if (d) {
            startDisplay.value = formatDisplay(d);
            startIso.value = toIso(d);
          } else {
            startDisplay.value = '';
            startIso.value = null;
          }
          updatePreCount();
        },
      });
    }
    if (endFlat.value) {
      flatpickr(endFlat.value, {
        dateFormat: 'd/m/Y',
        allowInput: true,
        defaultDate: endIso.value || null,
        onChange: (selectedDates) => {
          const d = selectedDates[0] || null;
          endDateObj.value = d;
          if (d) {
            endDisplay.value = formatDisplay(d);
            endIso.value = toIso(d);
          } else {
            endDisplay.value = '';
            endIso.value = null;
          }
          updatePreCount();
        },
      });
    }
  } catch (e) {
    console.warn('flatpickr init failed', e);
  }
});

// Cleanup function to prevent memory leaks
onUnmounted(() => {
  if (progressInterval) {
    clearInterval(progressInterval);
  }
});

// helpers: format Date -> dd/MM/yyyy and to ISO yyyy-mm-dd
const formatDisplay = (d) => {
  if (!d) return '';
  const day = String(d.getDate()).padStart(2, '0');
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const yr = d.getFullYear();
  return `${day}/${mo}/${yr}`;
};

const toIso = (d) => {
  if (!d) return null;
  const day = String(d.getDate()).padStart(2, '0');
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const yr = d.getFullYear();
  return `${yr}-${mo}-${day}`;
};

// (native date inputs are used; handlers below manage display ↔ ISO sync)

// parse dd/mm/yyyy -> yyyy-mm-dd or return null
const parseDisplayToIso = (s) => {
  if (!s) return null;
  const m = s.trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (!m) return null;
  const d = m[1].padStart(2, '0');
  const mo = m[2].padStart(2, '0');
  const y = m[3];
  // simple validation
  const iso = `${y}-${mo}-${d}`;
  const dt = new Date(iso);
  if (isNaN(dt.getTime())) return null;
  return iso;
};

const onStartDisplayInput = () => {
  const iso = parseDisplayToIso(startDisplay.value);
  startIso.value = iso;
};

const onEndDisplayInput = () => {
  const iso = parseDisplayToIso(endDisplay.value);
  endIso.value = iso;
};

const openStartPicker = () => {
  const el = startDateInput.value;
  if (!el) return;
  // modern browsers support showPicker()
  if (typeof el.showPicker === 'function') {
    try {
      el.showPicker();
      return;
    } catch (e) {
      /* fallthrough */
    }
  }
  // fallback: focus the input to trigger the native UI
  try {
    el.focus();
  } catch (e) {
    /* ignore */
  }
};

const openEndPicker = () => {
  const el = endDateInput.value;
  if (!el) return;
  if (typeof el.showPicker === 'function') {
    try {
      el.showPicker();
      return;
    } catch (e) {
      /* fallthrough */
    }
  }
  try {
    el.focus();
  } catch (e) {
    /* ignore */
  }
};

// convert Date object -> ISO (yyyy-mm-dd)
const dateToIso = (d) => {
  if (!d) return null;
  const yy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yy}-${mm}-${dd}`;
};

watch([startDateObj, endDateObj], () => {
  startIso.value = dateToIso(startDateObj.value);
  endIso.value = dateToIso(endDateObj.value);
  startDisplay.value = startIso.value
    ? `${startIso.value.split('-')[2]}/${startIso.value.split('-')[1]}/${startIso.value.split('-')[0]}`
    : '';
  endDisplay.value = endIso.value
    ? `${endIso.value.split('-')[2]}/${endIso.value.split('-')[1]}/${endIso.value.split('-')[0]}`
    : '';
  updatePreCount();
});

const humanDate = (iso) => {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    const months = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember',
    ];
    const day = String(d.getDate()).padStart(2, '0');
    return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
  } catch (e) {
    return iso;
  }
};

const openReport = async (id) => {
  modalOpen.value = true;
  modalReport.value = { id };
  const r = await fetch(`/api/admin/analytics/reports/${id}`);
  modalReport.value = await r.json();
};

const closeModal = () => {
  modalOpen.value = false;
  modalReport.value = {};
};

const modalCategoryOptions = computed(() => {
  const cats = (modalReport.value?.summary_json?.categories || [])
    .slice()
    .sort((a, b) => b.count - a.count);
  const labels = cats.map((c) => c.label || c.name);
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 6 } },
    dataLabels: { enabled: false },
    xaxis: { categories: labels },
  };
});

const modalCategorySeries = computed(() => [
  {
    name: 'Messages',
    data: (modalReport.value?.summary_json?.categories || []).map(
      (c) => c.count,
    ),
  },
]);

const topicBars = computed(() => {
  const topics = reportSummary.value?.summary_json?.topics || [];
  if (!topics.length) return [];
  const max = Math.max(...topics.map((t) => t.count), 1);
  return topics.map((t) => ({
    label: t.label,
    count: t.count,
    width: Math.round((t.count / max) * 100),
  }));
});

const categoryChartOptions = computed(() => {
  const cats = (reportSummary.value?.summary_json?.categories || [])
    .slice()
    .sort((a, b) => b.count - a.count);
  const labels = cats.map((c) => c.label || c.name);
  const data = cats.map((c) => c.count);
  return {
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: {
      bar: { horizontal: true, distributed: false, borderRadius: 6 },
    },
    dataLabels: { enabled: false },
    xaxis: { categories: labels, labels: { style: { fontSize: '13px' } } },
    yaxis: { labels: { style: { fontSize: '13px' } } },
    responsive: [{ breakpoint: 1024, options: { chart: { height: 420 } } }],
    colors: ['#2563eb'],
  };
});

const categorySeries = computed(() => {
  const cats = (reportSummary.value?.summary_json?.categories || [])
    .slice()
    .sort((a, b) => b.count - a.count);
  return [{ name: 'Messages', data: cats.map((c) => c.count) }];
});

// (single confirmStart above includes sample flag and starts polling tokenized)

// UI constants and helpers
const topCount = 3;

const topDetailedRecs = computed(() => {
  const recs = reportSummary.value?.summary_json?.recommendations_detailed || [];
  return recs.slice(0, topCount);
});

const perTopicInsightsLimited = computed(() => {
  const pti = reportSummary.value?.summary_json?.per_topic_insights;
  const strategies = reportSummary.value?.summary_json?.top_topics_strategies || {};
  const entries = pti && !Array.isArray(pti) ? Object.entries(pti) : [];
  return entries.slice(0, topCount).map(([key, text]) => ({
    topic_key: key,
    label: strategies?.[key]?.label || key,
    count: strategies?.[key]?.count,
    text,
  }));
});

const modalPerTopicInsightsLimited = computed(() => {
  const pti = modalReport.value?.summary_json?.per_topic_insights;
  const strategies = modalReport.value?.summary_json?.top_topics_strategies || {};
  const entries = pti && !Array.isArray(pti) ? Object.entries(pti) : [];
  return entries.slice(0, topCount).map(([key, text]) => ({
    topic_key: key,
    label: strategies?.[key]?.label || key,
    count: strategies?.[key]?.count,
    text,
  }));
});

// Single combined insight text (AI-only; no static fallbacks)
const combinedInsightText = computed(() => {
  // Prefer root-level persisted fields when present
  const rootTxt = reportSummary.value?.combined_top_insight;
  if (rootTxt && typeof rootTxt === 'string' && rootTxt.trim().length > 0) return rootTxt;
  const txt = reportSummary.value?.summary_json?.combined_top_insight;
  if (txt && typeof txt === 'string' && txt.trim().length > 0) return txt;
  const rootSummary = reportSummary.value?.insight_summary;
  if (
    rootSummary
    && typeof rootSummary === 'string'
    && rootSummary.trim().length > 0
  ) return rootSummary;
  const detailed = reportSummary.value?.summary_json?.detailed_analysis;
  if (detailed && typeof detailed === 'string' && detailed.trim().length > 0) return detailed;
  const summary = reportSummary.value?.summary_json?.insight_summary;
  if (summary && typeof summary === 'string' && summary.trim().length > 0) return summary;
  return 'Menunggu hasil analisis AI… jalankan Start Analysis atau refresh laporan.';
});

const modalCombinedInsightText = computed(() => {
  const rootTxt = modalReport.value?.combined_top_insight;
  if (rootTxt && typeof rootTxt === 'string' && rootTxt.trim().length > 0) return rootTxt;
  const txt = modalReport.value?.summary_json?.combined_top_insight;
  if (txt && typeof txt === 'string' && txt.trim().length > 0) return txt;
  const rootSummary = modalReport.value?.insight_summary;
  if (
    rootSummary
    && typeof rootSummary === 'string'
    && rootSummary.trim().length > 0
  ) return rootSummary;
  const detailed = modalReport.value?.summary_json?.detailed_analysis;
  if (detailed && typeof detailed === 'string' && detailed.trim().length > 0) return detailed;
  const summary = modalReport.value?.summary_json?.insight_summary;
  if (summary && typeof summary === 'string' && summary.trim().length > 0) return summary;
  return 'Menunggu hasil analisis AI…';
});

// Split long AI text into readable paragraphs. Prefer double newlines; fallback to sentence chunks.
// Simple markdown renderer for basic formatting
const renderMarkdown = (text) => {
  if (!text || typeof text !== 'string') return '';

  return (
    text
      // Headers
      .replace(
        /^# (.*$)/gim,
        '<h1 class="text-2xl font-bold mb-4 text-gray-800">$1</h1>',
      )
      .replace(
        /^## \*\*(.*?)\*\*$/gim,
        '<h2 class="text-xl font-semibold mb-3 mt-6 text-blue-800 border-b border-blue-200 pb-2">$1</h2>',
      )
      .replace(
        /^## (.*$)/gim,
        '<h2 class="text-xl font-semibold mb-3 mt-6 text-blue-800">$1</h2>',
      )
      .replace(
        /^### (.*$)/gim,
        '<h3 class="text-lg font-medium mb-2 mt-4 text-gray-700">$1</h3>',
      )
      // Bold text
      .replace(
        /\*\*(.*?)\*\*/g,
        '<strong class="font-semibold text-gray-800">$1</strong>',
      )
      // Italic text
      .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
      // Line breaks
      .replace(/\n\n/g, '</p><p class="mb-3">')
      .replace(/\n/g, '<br>')
      // Lists
      .replace(/^- (.*$)/gim, '<li class="ml-4 mb-1">• $1</li>')
      // Wrap in paragraph
      .replace(/^(.+)$/gim, '<p class="mb-3">$1</p>')
      // Clean up nested paragraphs
      .replace(/<p class="mb-3"><h([1-6])/g, '<h$1')
      .replace(/<\/h([1-6])><\/p>/g, '</h$1>')
      .replace(/<p class="mb-3"><li/g, '<li')
      .replace(/<\/li><\/p>/g, '</li>')
  );
};

const paragraphize = (text) => {
  if (!text || typeof text !== 'string') return [];
  const t = text.trim();
  if (!t) return [];
  // If the text already has blank lines, split on them
  const byDoubleNewline = t.split(/\n\s*\n+/);
  const nonEmpty = byDoubleNewline
    .map((p) => p.trim())
    .filter((p) => p.length > 0);
  if (nonEmpty.length > 1) return nonEmpty;
  // Otherwise chunk by sentences (~3-4 sentences per paragraph)
  const sentences = t.split(/(?<=[.!?])\s+(?=[A-ZÀ-ÖØ-Þ0-9])/u);
  const out = [];
  let buf = [];
  sentences.forEach((s) => {
    buf.push(s);
    if (buf.join(' ').length > 400 || buf.length >= 4) {
      out.push(buf.join(' '));
      buf = [];
    }
  });
  if (buf.length) out.push(buf.join(' '));
  return out;
};

const analyticsSteps = [
  { title: 'Halaman Analitik AI', intro: 'Halaman ini memungkinkan Anda menganalisis pola percakapan pengguna dan mendapatkan laporan insight berbasis AI.' },
  { element: '#tour-analytics-filter', title: 'Filter Tanggal', intro: 'Pilih rentang tanggal "Dari" dan "Sampai" untuk menentukan periode data yang akan dianalisis.' },
  { element: '#tour-analytics-actions', title: 'Tombol Analisis', intro: 'Klik "Generate Analysis" untuk memproses data chat pada rentang tanggal yang dipilih. Proses ini menggunakan AI dan membutuhkan beberapa detik.' },
  { element: '#tour-analytics-insights', title: 'Laporan AI Insights', intro: 'Setelah analisis selesai, hasil laporan akan muncul di sini berupa ringkasan, topik populer, sentimen pengguna, dan rekomendasi.' },
];
const { startTour } = useTour(analyticsSteps);

</script>

<style scoped>
/* minimal styles */
</style>
