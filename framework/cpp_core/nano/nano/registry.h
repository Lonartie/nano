#pragma once
#include "export.h"
#include "service.h"
#include <map>
#include <functional>
#include <type_traits>
#include <QString>
#include <memory>

namespace nano
{
	class NANO_EXPORT registry
	{
	public:
		using creator = std::function<std::unique_ptr<service>()>;
		
		template <typename T>
		static void reg();

		static std::map<QString, creator> creators;
	};

	template <typename T>
	void registry::reg()
	{
		static_assert(std::is_base_of_v<service, T>, "T must be of type nano::service!");
		static_assert(std::is_default_constructible_v<T>, "T must be default constructible!");

		creator c = []() { return std::make_unique<T>(); };
		creators[c()->shortName()] = std::move(c);
	}
}

#define _NANO_CAT(a, b) a##b
#define NANO_CAT(a, b) _NANO_CAT(a, b)

#define REGISTER_SERVICE(NAME)							\
namespace														\
{																	\
	static auto NANO_CAT(service_,__LINE__) = []()	\
	{																\
		nano::registry::reg<NAME>();						\
		return true;											\
	}();															\
}
