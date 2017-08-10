port module CurrySearchAutoComplete exposing (..)

import Html exposing (..)
import Html.Attributes exposing (..)
import Html.Events exposing (..)
import Http
import Task
import Process
import Time
import Json.Decode exposing (..)
import Json.Encode


port suggest_choice : String -> Cmd msg


port search_box_input : (String -> msg) -> Sub msg


port search_box_arrow : (Int -> msg) -> Sub msg



-- Subscriptions


subscriptions : Model -> Sub Msg
subscriptions model =
    Sub.batch
        [ search_box_input SetQuery
        , search_box_arrow ArrowKey
        ]


main =
    Html.programWithFlags
        { init = init
        , view = view
        , update = update
        , subscriptions = subscriptions
        }



-- Flags


type alias Flags =
    { url : String
    , public_api_key : String
    , session_hash : String
    }



-- Model


type alias Model =
    { focus : Int
    , public_api_key : String
    , session_hash : String
    , url : String
    , query : String
    , suggestions : List String
    , queuedChanges : Int
    }



-- Init


init : Flags -> ( Model, Cmd Msg )
init flags =
    ( { focus = 0
      , url = flags.url
      , public_api_key = flags.public_api_key
      , session_hash = flags.session_hash
      , query = ""
      , suggestions = []
      , queuedChanges = 0
      }
    , Cmd.none
    )



-- API CALLS


type alias GetSuggestionsRequest =
    { query : String
    , caret : Int
    , security : String
    }


getSuggestions : Model -> Cmd Msg
getSuggestions model =
    let
        request =
            Json.Encode.object
                [ ( "query", Json.Encode.string model.query )
                , ( "caret_pos", Json.Encode.int 0 )
                ]
    in
        Http.send NewSuggestions (Http.request
                                      { method = "POST"
                                      , headers = [ Http.header "X-CS-PubApiKey" model.public_api_key
                                                  , Http.header "X-CS-SessionHash" model.session_hash
                                                  ]
                                      , url = model.url
                                      , body = Http.jsonBody request
                                      , expect = Http.expectJson (Json.Decode.list string)
                                      , timeout = Nothing
                                      , withCredentials = False
                                      })



-- Update


type Msg
    = SetQuery String
    | ArrowKey Int
    | Select Int
    | GetSuggestions
    | NewSuggestions (Result Http.Error (List String))


update : Msg -> Model -> ( Model, Cmd Msg )
update msg model =
    case msg of
        SetQuery newQuery ->
            ( { model | query = newQuery, queuedChanges = model.queuedChanges + 1 }
            , Process.sleep (100 * Time.millisecond)
                |> Task.perform (\_ -> GetSuggestions)
            )

        GetSuggestions ->
            if model.queuedChanges == 1 then
                ( dequeue model
                , getSuggestions model
                )
            else
                ( dequeue model, Cmd.none )

        NewSuggestions (Ok newSuggestions) ->
            ( { model | suggestions = newSuggestions }, Cmd.none )

        NewSuggestions (Err e) ->
            ( model,  Cmd.none )

        Select index ->
            ( new_focus { model | focus = index } )

        ArrowKey index ->
            ( new_focus { model | focus = model.focus + index } )


dequeue : Model -> Model
dequeue model =
    { model | queuedChanges = model.queuedChanges - 1 }


enqueue : Model -> Model
enqueue model =
    { model | queuedChanges = model.queuedChanges + 1 }

new_focus : Model -> (Model, Cmd Msg)
new_focus model =
    (model, suggest_choice (Maybe.withDefault model.query (elementAt model.suggestions model.focus)))


-- Helper


elementAt : List a -> Int -> Maybe a
elementAt list n =
    case List.drop (n - 1) list of
        [] ->
            Nothing

        y :: ys ->
            Just y



-- View


view : Model -> Html Msg
view model =
    if List.isEmpty model.suggestions then
        Html.text ""
    else
        ul [ class "cs_ac_results" ]
            (List.indexedMap
                (\index ->
                    \sug ->
                        if index == model.focus - 1 then
                            li [ class "cs_ac_active" ] [ text sug ]
                        else
                            li [ onClick (Select (index + 1)) ] [ text sug ]
                )
                model.suggestions
            )
