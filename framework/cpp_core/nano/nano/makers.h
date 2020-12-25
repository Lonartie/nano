#pragma once
#include "service.h"
#include "method.h"
#include "parameter.h"
#include <array>
#include <tuple>
#include <optional>
#include <QVariant>

namespace nano
{

	template <typename T> struct is_optional : std::false_type {};
	template <typename T> struct is_optional<std::optional<T>> : std::true_type {};
	template <typename T> constexpr bool is_optional_v = is_optional<T>::value;

	template <typename T> struct inner_optional { using type = T; };
	template <typename T> struct inner_optional<std::optional<T>> { using type = T; };
	template <typename T> using inner_optional_t = typename inner_optional<T>::type;
	
	static QJsonObject __json_success()
	{
		return {{ "success", true }};
	};

	template <typename T>
	static QJsonObject __json_success(T&& val)
	{
		static_assert(std::is_constructible_v<QJsonValue, T>, "QJsonValue must be constructible with T!");
		return {{ "success", true }, { "result", QJsonValue(std::forward<T>(val)) }};
	}

	template <typename T>
	T _to(const QString&) = delete;

#define DEFINE_QSTRING_TO(TYPE, SHORT) \
template <> inline TYPE _to<TYPE>(const QString& str) { return str.to##SHORT(); } \

	template <> inline QString _to<QString>(const QString& str) { return str; }

	DEFINE_QSTRING_TO(int, Int);
	DEFINE_QSTRING_TO(unsigned int, UInt);
	DEFINE_QSTRING_TO(long, Long);
	DEFINE_QSTRING_TO(unsigned long, ULong);
	DEFINE_QSTRING_TO(unsigned long long, ULongLong);
	DEFINE_QSTRING_TO(float, Float);
	DEFINE_QSTRING_TO(double, Double);
	DEFINE_QSTRING_TO(short, Short);
	DEFINE_QSTRING_TO(unsigned short, UShort);

	template <> inline bool _to<bool>(const QString& str) { auto ok = false; const auto num = str.toInt(&ok); if (ok) return static_cast<bool>(num); return str.toLower() == "true"; }
	template <> inline char _to<char>(const QString& str) { return str.at(0).toLatin1(); }
	template <> inline QChar _to<QChar>(const QString& str) { return str.at(0); }

	template <typename T>
	static T to(const QString& str)
	{
		if constexpr (is_optional_v<T>)
		{
			if (str == "std::nullopt") return std::nullopt;
		}

		if (str == "std::nullopt") throw std::exception("missing non-optional argument");
		return _to<inner_optional_t<T>>(str);
	}

	template <std::size_t ... I>
	auto make_tuple(QStringList list, std::index_sequence<I...> = {})
	{
		return std::make_tuple((I < list.size() ? list.at(I) : QString("std::nullopt")) ...);
	}

	void transform_param(std::vector<parameter>& params, const std::size_t i, const bool is_op)
	{
		params.at(i).optional = is_op;
	}
	
	template <typename ... ARGS, std::size_t ... I>
	void transform_params(std::vector<parameter>& params, std::index_sequence<I...> = {})
	{
		(transform_param(params, I, is_optional_v<ARGS>),...);
	}

	template <typename T, typename ... ARGS>
	method make_method(QString name, void(T::* func)(ARGS ...), std::array<QString, sizeof...(ARGS)> names)
	{
		std::vector<QString> nms(names.begin(), names.end());
		std::vector<parameter> params;
		for (auto& name : nms) params.push_back(parameter{name, false});
		transform_params<ARGS...>(params, std::index_sequence_for<ARGS...>{});
		method::func_ptr_type fptr = [func](service* s, QStringList a)
		{
			std::index_sequence_for<ARGS...> seq;
			auto fnc = [s, func](auto&& ... args)
			{
				((*static_cast<T*>(s)).*func)(to<std::decay_t<ARGS>>(args)...);
				return __json_success();
			};
			return std::apply(fnc, make_tuple(a, seq));
		};
		return method{std::move(name), std::move(params), std::move(fptr)};
	}

	template <typename ... ARGS>
	method make_method(QString name, void(*func)(ARGS ...), std::array<QString, sizeof...(ARGS)> names)
	{
		std::vector<QString> nms(names.begin(), names.end());
		std::vector<parameter> params;
		for (auto& name : nms) params.push_back(parameter{name, false});
		transform_params<ARGS...>(params, std::index_sequence_for<ARGS...>{});
		method::func_ptr_type fptr = [func](service* s, QStringList a)
		{
			std::index_sequence_for<ARGS...> seq;
			auto fnc = [s, func](auto&& ... args)
			{
				(*func)(to<std::decay_t<ARGS>>(args)...);
				return __json_success();
			};
			return std::apply(fnc, make_tuple(a, seq));
		};
		return method{std::move(name), std::move(params), std::move(fptr)};
	}

	template <typename R, typename T, typename ... ARGS, typename = std::enable_if_t<!std::is_same_v<R, void>>>
	method make_method(QString name, R(T::* func)(ARGS ...), std::array<QString, sizeof...(ARGS)> names)
	{
		std::vector<QString> nms(names.begin(), names.end());
		std::vector<parameter> params;
		for (auto& name : nms) params.push_back(parameter{name, false});
		transform_params<ARGS...>(params, std::index_sequence_for<ARGS...>{});
		method::func_ptr_type fptr = [func](service* s, QStringList a)
		{
			std::index_sequence_for<ARGS...> seq;
			auto fnc = [s, func](auto&& ... args)
			{
				auto val = ((*static_cast<T*>(s)).*func)(to<std::decay_t<ARGS>>(args)...);
				return __json_success(val);
			};
			return std::apply(fnc, make_tuple(a, seq));
		};
		return method{std::move(name), std::move(params), std::move(fptr)};
	}

	template <typename R, typename ... ARGS, typename = std::enable_if_t<!std::is_same_v<R, void>>>
	method make_method(QString name, R(*func)(ARGS ...), std::array<QString, sizeof...(ARGS)> names)
	{
		std::vector<QString> nms(names.begin(), names.end());
		std::vector<parameter> params;
		for (auto& name : nms) params.push_back(parameter{name, false});
		transform_params<ARGS...>(params, std::index_sequence_for<ARGS...>{});
		method::func_ptr_type fptr = [func](service* s, QStringList a)
		{
			std::index_sequence_for<ARGS...> seq;
			auto fnc = [s, func](auto&& ... args)
			{
				auto val = (*func)(to<std::decay_t<ARGS>>(args)...);
				return __json_success(val);
			};
			return std::apply(fnc, make_tuple(a, seq));
		};
		return method{std::move(name), std::move(params), std::move(fptr)};
	}

	// PARAMETERS

}