<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Get chat history for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $messages = ChatMessage::forUser(Auth::id())
                ->recent(50)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching chat messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الرسائل'
            ], 500);
        }
    }

    /**
     * Send a message to AI and get response
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        try {
            $userMessage = $request->input('message');
            $userId = Auth::id();

            // حفظ رسالة المستخدم
            $chatMessage = ChatMessage::create([
                'user_id' => $userId,
                'message' => $userMessage,
                'type' => 'user'
            ]);

            // إرسال الرسالة إلى OpenAI
            $aiResponse = $this->getAIResponse($userMessage);

            // حفظ رد AI
            $aiMessage = ChatMessage::create([
                'user_id' => $userId,
                'message' => $aiResponse,
                'type' => 'ai',
                'metadata' => [
                    'related_user_message_id' => $chatMessage->id
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_message' => $chatMessage,
                    'ai_response' => $aiMessage
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending chat message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الرسالة'
            ], 500);
        }
    }

    /**
     * Clear chat history for the authenticated user
     */
    public function clearHistory(): JsonResponse
    {
        try {
            ChatMessage::forUser(Auth::id())->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم مسح تاريخ المحادثة بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing chat history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في مسح تاريخ المحادثة'
            ], 500);
        }
    }

    /**
     * Get AI response from OpenAI
     */
    private function getAIResponse(string $message): string
    {
        try {
            $apiKey = env('OPENAI_API_KEY');
            
            if (!$apiKey) {
                return 'عذراً، لم يتم تكوين مفتاح API للذكاء الاصطناعي.';
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد ذكي متخصص في نظام التقييم العقاري. يمكنك مساعدة المستخدمين في أسئلة متعلقة بالتقييم العقاري، والاستشارات، والنصائح العامة. أجب باللغة العربية بطريقة مفيدة ومهنية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'عذراً، لم أتمكن من فهم طلبك.';
            } else {
                Log::error('OpenAI API Error: ' . $response->body());
                return 'عذراً، حدث خطأ في الاتصال بخدمة الذكاء الاصطناعي.';
            }

        } catch (\Exception $e) {
            Log::error('AI Response Error: ' . $e->getMessage());
            return 'عذراً، حدث خطأ في معالجة طلبك. يرجى المحاولة مرة أخرى.';
        }
    }

//     private function getAIResponse(string $message): string
// {
//     try {
//         $apiKey = env('OPENROUTER_API_KEY');

//         if (!$apiKey) {
//             return 'عذراً، لم يتم تكوين مفتاح OpenRouter.';
//         }

//         $response = Http::withHeaders([
//             'Authorization' => 'Bearer ' . $apiKey,
//             'Content-Type' => 'application/json',
//         ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
//             'model' => 'openchat/openchat-3.5-1210', // أو gpt-3.5-turbo إن أردت
//             'messages' => [
//                 [
//                     'role' => 'system',
//                     'content' => 'أنت مساعد ذكي متخصص في نظام التقييم العقاري. أجب باللغة العربية باحترافية.'
//                 ],
//                 [
//                     'role' => 'user',
//                     'content' => $message
//                 ]
//             ],
//             'max_tokens' => 500,
//             'temperature' => 0.7
//         ]);

//         if ($response->successful()) {
//             $data = $response->json();
//             return $data['choices'][0]['message']['content'] ?? 'عذراً، لم أتمكن من فهم طلبك.';
//         } else {
//             Log::error('OpenRouter API Error: ' . $response->body());
//             return 'عذراً، حدث خطأ في الاتصال بخدمة الذكاء الاصطناعي.';
//         }

//     } catch (\Exception $e) {
//         Log::error('AI Response Error: ' . $e->getMessage());
//         return 'عذراً، حدث خطأ في معالجة طلبك. يرجى المحاولة مرة أخرى.';
//     }
// }

}

